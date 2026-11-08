<?php

declare(strict_types=1);

namespace Kami\Cocktail\Scraper;

use GuzzleHttp\Handler\CurlHandler;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Symfony\Component\HttpFoundation\IpUtils;
use Kami\Cocktail\Exceptions\ScraperMissingException;
use Kevinrob\GuzzleCache\Storage\LaravelCacheStorage;
use Kevinrob\GuzzleCache\Strategy\GreedyCacheStrategy;

/**
 * Fetches untrusted scraping URLs while restricting access to internal networks.
 *
 * DNS answers are checked before the connection and pinned in cURL to prevent
 * DNS rebinding. Redirects are disallowed. A configured proxy may resolve the
 * destination independently, so it must enforce its own network restrictions.
 */
final class ScraperHttpClient
{
    /**
     * Fetch a URL without following redirects, limiting the response to 10 MiB.
     *
     * Automatic decompression is disabled so compressed responses cannot bypass
     * the download limit. The optional cache is used for recipe pages.
     *
     * @throws ScraperMissingException if the destination or response is unsafe
     */
    public function get(string $url, bool $cache = false): Response
    {
        if (!extension_loaded('curl')) {
            throw new ScraperMissingException('The cURL extension is required for safe scraping.');
        }

        [$host, $port, $address] = $this->resolveDestination($url);
        $resolvedAddress = str_contains($address, ':') ? '[' . $address . ']' : $address;
        // Pin the validated address for direct connections so cURL cannot resolve it again.
        $curlOptions = [CURLOPT_RESOLVE => [$host . ':' . $port . ':' . $resolvedAddress]];
        $proxy = config('bar-assistant.scraping_client.proxy');
        if ($proxy) {
            Log::warning('Scraping through a proxy may bypass destination address checks; configure the proxy to block non-public addresses.');
        }

        $options = [
            'allow_redirects' => false,
            'decode_content' => false,
            'proxy' => $proxy ?: '',
            'curl' => $curlOptions,
            'progress' => static function (int $total, int $downloaded): void {
                if ($downloaded > 10 * 1024 * 1024) {
                    throw new ScraperMissingException('Scraping response exceeds the maximum size.');
                }
            },
        ];
        if ($proxy && config('bar-assistant.scraping_client.cert')) {
            $options['verify'] = config('bar-assistant.scraping_client.cert');
        }

        $request = Http::setHandler(new CurlHandler())
            ->withUserAgent(Manager::USER_AGENT)
            ->withOptions($options)
            ->withHeaders(['Accept-Encoding' => 'identity'])
            ->timeout(10);

        if ($cache) {
            $request = $request->withMiddleware(new CacheMiddleware(new GreedyCacheStrategy(new LaravelCacheStorage(Cache::store()), 60 * 15)));
        }

        $response = $request->get($url);

        if ($response->redirect()) {
            throw new ScraperMissingException('Scraping redirects are not allowed.');
        }

        return $response;
    }

    /**
     * Resolve a hostname and reject the entire result if any address is non-public.
     *
     * Only HTTP(S) hostnames without credentials or explicit ports are accepted.
     * Additional IPv6 transition and multicast ranges are blocked because they
     * can route traffic to addresses that would otherwise be rejected.
     *
     * @return array{string, int, string} Hostname, default port, and validated IP to pin
     * @throws ScraperMissingException if the URL or any resolved address is unsafe
     */
    private function resolveDestination(string $url): array
    {
        $parts = parse_url($url);
        if ($parts === false || !isset($parts['scheme'], $parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
            throw new ScraperMissingException('Invalid scraping URL.');
        }

        if (isset($parts['port'])) {
            throw new ScraperMissingException('Scraping URLs with explicit ports is not allowed.');
        }

        $scheme = strtolower($parts['scheme']);
        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new ScraperMissingException('Only HTTP and HTTPS URLs can be scraped.');
        }

        $host = strtolower(trim($parts['host'], '[]'));
        $port = $scheme === 'https' ? 443 : 80;
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            throw new ScraperMissingException('Scraping URLs with IP addresses is not allowed.');
        }

        // Numeric hostnames can be interpreted as IP addresses by cURL.
        if (!preg_match('/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)*[a-z](?:[a-z0-9-]{0,61}[a-z0-9])?$/i', $host)) {
            throw new ScraperMissingException('Invalid scraping hostname.');
        }

        $records = @dns_get_record($host, DNS_A | DNS_AAAA);
        if ($records === false || $records === []) {
            throw new ScraperMissingException('Unable to resolve scraping hostname.');
        }

        $addresses = [];
        foreach ($records as $record) {
            $address = $record['ip'] ?? $record['ipv6'] ?? null;
            if (is_string($address)) {
                $addresses[] = $address;
            }
        }

        if ($addresses === []) {
            throw new ScraperMissingException('Unable to resolve scraping hostname.');
        }

        foreach ($addresses as $address) {
            if (!filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_GLOBAL_RANGE) || IpUtils::isPrivateIp($address)) {
                throw new ScraperMissingException('Scraping non-public network addresses is not allowed.');
            }
        }

        return [$host, $port, $addresses[0]];
    }
}
