<?php

declare(strict_types=1);

namespace Kami\Cocktail\Scraper;

/**
 * Local RFC 9309 robots.txt evaluator.
 *
 * Known simplifications:
 * - No percent-encoding normalization of URLs
 * - No sitemap:, crawl-delay:, or mime: rules are handled
 */
final class RobotsTxtEvaluator
{
    /**
     * Parsed rule groups.
     *
     * @var list<array{
     *     userAgents: list<string>,
     *     rules: list<array{regex: string, allow: bool}>
     * }>
     */
    private readonly array $groups;

    public function __construct(private readonly string $content)
    {
        $this->groups = $this->parse($content);
    }

    public function allows(string $url, string $userAgent): bool
    {
        $path = $this->urlPath($url);
        if ($path === null || $path === '') {
            return true;
        }

        $group = $this->selectGroup($userAgent);
        if ($group === null) {
            return true;
        }

        $bestLength = -1;
        $bestIsAllow = true;

        foreach ($group['rules'] as $rule) {
            $result = @preg_match($rule['regex'], $path, $matches);
            if ($result !== 1) {
                continue;
            }

            $length = strlen($matches[0]);

            if ($length > $bestLength) {
                $bestLength = $length;
                $bestIsAllow = $rule['allow'];
            } elseif ($length === $bestLength && $rule['allow']) {
                $bestIsAllow = true;
            }
        }

        return $bestIsAllow;
    }

    /**
     * @return list<array{
     *     userAgents: list<string>,
     *     rules: list<array{regex: string, allow: bool}>
     * }>
     */
    private function parse(string $content): array
    {
        /** @var list<array{userAgents: list<string>, rules: list<array{regex: string, allow: bool}>}> $groups */
        $groups = [];

        /** @var array{userAgents: list<string>, rules: list<array{regex: string, allow: bool}>}|null $currentGroup */
        $currentGroup = null;
        $lastWasUserAgent = false;

        $lines = preg_split('/\r\n|\r|\n/', $content);
        if ($lines === false) {
            return [];
        }

        foreach ($lines as $rawLine) {
            $line = trim($this->stripComment($rawLine));
            if ($line === '') {
                continue;
            }

            if (preg_match('/^user-agent:\s*(.+)$/i', $line, $matches) === 1) {
                $token = trim($matches[1]);
                if ($token === '') {
                    $lastWasUserAgent = false;

                    continue;
                }

                if ($lastWasUserAgent && $currentGroup !== null) {
                    $currentGroup['userAgents'][] = $token;
                } else {
                    if ($currentGroup !== null) {
                        $groups[] = $currentGroup;
                    }

                    $currentGroup = ['userAgents' => [$token], 'rules' => []];
                }

                $lastWasUserAgent = true;

                continue;
            }

            $lastWasUserAgent = false;

            if ($currentGroup === null) {
                continue;
            }

            $rule = $this->compileRule($line);
            if ($rule !== null) {
                $currentGroup['rules'][] = $rule;
            }
        }

        if ($currentGroup !== null) {
            $groups[] = $currentGroup;
        }

        return $groups;
    }

    /**
     * @return array{regex: string, allow: bool}|null
     */
    private function compileRule(string $line): ?array
    {
        if (preg_match('/^allow:\s*(.*)$/i', $line, $matches) === 1) {
            $allow = true;
            $pattern = trim($matches[1]);
        } elseif (preg_match('/^disallow:\s*(.*)$/i', $line, $matches) === 1) {
            $allow = false;
            $pattern = trim($matches[1]);
        } else {
            return null;
        }

        if ($pattern === '') {
            return null;
        }

        $anchored = str_ends_with($pattern, '$');
        if ($anchored) {
            $pattern = substr($pattern, 0, -1);
        }

        $regex = str_replace('\\*', '.*', preg_quote($pattern, '~'));
        $regex = $anchored ? '~^' . $regex . '$~u' : '~^' . $regex . '~u';

        return ['regex' => $regex, 'allow' => $allow];
    }

    /**
     * @return array{userAgents: list<string>, rules: list<array{regex: string, allow: bool}>}|null
     */
    private function selectGroup(string $userAgent): ?array
    {
        foreach ($this->groups as $group) {
            foreach ($group['userAgents'] as $groupUserAgent) {
                if (strcasecmp($groupUserAgent, $userAgent) === 0) {
                    return $group;
                }
            }
        }

        foreach ($this->groups as $group) {
            foreach ($group['userAgents'] as $groupUserAgent) {
                if ($groupUserAgent === '*') {
                    return $group;
                }
            }
        }

        return null;
    }

    private function urlPath(string $url): ?string
    {
        $parts = parse_url($url);
        if ($parts === false) {
            return null;
        }

        $path = $parts['path'] ?? null;
        if ($path === null) {
            return null;
        }

        $query = $parts['query'] ?? null;

        return $query === null ? $path : $path . '?' . $query;
    }

    private function stripComment(string $line): string
    {
        $commentPosition = strpos($line, '#');
        if ($commentPosition === false) {
            return $line;
        }

        return substr($line, 0, $commentPosition);
    }
}
