<?php

declare(strict_types=1);

namespace Tests\Unit\Scraper;

use Tests\TestCase;
use Kami\Cocktail\Scraper\RobotsTxtEvaluator;
use PHPUnit\Framework\Attributes\DataProvider;

class RobotsTxtEvaluatorTest extends TestCase
{
    private const string PRODUCT_TOKEN = 'BarAssistantBot';

    private const string KINDRED_COCKTAILS_ROBOTS_TXT = <<<'TXT'
# robots.txt (trimmed to relevant rules)
User-agent: *
Allow: /core/*.css$
Allow: /core/*.js$
Allow: /profiles/*.css$
Disallow: /core/
Disallow: /profiles/
Disallow: /README.md
Disallow: /search/
Disallow: /search?
Disallow: /index.php/cocktail/
Disallow: /*/media/oembed
Allow: /cocktail/*
Allow: /cocktail/*/
Disallow: /cocktail/
Allow: /index.php/cocktail/*
Allow: /index.php/cocktail/*/
Disallow: /index.php/cocktail/
TXT;

    #[DataProvider('kindredCocktailsProvider')]
    public function testKindredCocktails(string $url, bool $expected): void
    {
        $evaluator = new RobotsTxtEvaluator(self::KINDRED_COCKTAILS_ROBOTS_TXT);

        $this->assertSame($expected, $evaluator->allows($url, self::PRODUCT_TOKEN));
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function kindredCocktailsProvider(): iterable
    {
        yield 'mr manhattan savoy is allowed' => ['https://kindredcocktails.com/cocktail/mr-manhattan-savoy', true];
        yield 'negroni is allowed' => ['https://kindredcocktails.com/cocktail/negroni', true];
        yield 'index.php cocktail is allowed' => ['https://kindredcocktails.com/index.php/cocktail/negroni', true];
        yield 'core directory is disallowed' => ['https://kindredcocktails.com/core/', false];
        yield 'readme file is disallowed' => ['https://kindredcocktails.com/README.md', false];
        yield 'search path is disallowed' => ['https://kindredcocktails.com/search/', false];
        yield 'cocktail path is allowed on tie' => ['https://kindredcocktails.com/cocktail/', true];
    }

    #[DataProvider('ruleMatchingProvider')]
    public function testRuleMatching(string $robotsTxt, string $url, bool $expected): void
    {
        $evaluator = new RobotsTxtEvaluator($robotsTxt);

        $this->assertSame($expected, $evaluator->allows($url, 'SomeBot'));
    }

    /**
     * @return iterable<string, array{string, string, bool}>
     */
    public static function ruleMatchingProvider(): iterable
    {
        yield 'end anchor blocks matching css' => ["User-agent: *\nDisallow: /core/*.css$\n", 'https://example.com/core/main.css', false];
        yield 'end anchor allows longer css map' => ["User-agent: *\nDisallow: /core/*.css$\n", 'https://example.com/core/main.css.map', true];
        yield 'mid-pattern wildcard blocks path' => ["User-agent: *\nDisallow: /index.php/cocktail/*\n", 'https://example.com/index.php/cocktail/negroni', false];
        yield 'query string is part of the matched path' => ["User-agent: *\nDisallow: /cocktail?print=true\n", 'https://example.com/cocktail?print=true', false];
        yield 'query string rule does not match bare path' => ["User-agent: *\nDisallow: /cocktail?print=true\n", 'https://example.com/cocktail', true];
        yield 'longest match wins in favor of allow' => ["User-agent: *\nDisallow: /cocktail/\nAllow: /cocktail/mr-manhattan-savoy\n", 'https://example.com/cocktail/mr-manhattan-savoy', true];
        yield 'longest match still blocks other paths' => ["User-agent: *\nDisallow: /cocktail/\nAllow: /cocktail/mr-manhattan-savoy\n", 'https://example.com/cocktail/negroni', false];
        yield 'tie breaks to allow' => ["User-agent: *\nDisallow: /cocktail/\nAllow: /cocktail/\n", 'https://example.com/cocktail/', true];
        yield 'rules before first user-agent are ignored' => ["Disallow: /before\nUser-agent: *\nDisallow: /after\n", 'https://example.com/before', true];
        yield 'rules after first user-agent apply' => ["Disallow: /before\nUser-agent: *\nDisallow: /after\n", 'https://example.com/after', false];
        yield 'empty robots.txt allows all' => ['', 'https://example.com/cocktail', true];
        yield 'invalid url fails open' => ["User-agent: *\nDisallow: /\n", 'http://', true];
        yield 'invalid utf-8 url fails open' => ["User-agent: *\nDisallow: /\n", "https://example.com/\xFF\xFE", true];
    }

    #[DataProvider('groupSelectionProvider')]
    public function testGroupSelection(string $robotsTxt, string $url, string $userAgent, bool $expected): void
    {
        $evaluator = new RobotsTxtEvaluator($robotsTxt);

        $this->assertSame($expected, $evaluator->allows($url, $userAgent));
    }

    /**
     * @return iterable<string, array{string, string, string, bool}>
     */
    public static function groupSelectionProvider(): iterable
    {
        $robots = "User-agent: BarAssistantBot\nDisallow: /secret\n\nUser-agent: *\nDisallow: /everything\n";

        yield 'exact product token match applies' => [$robots, 'https://example.com/secret', 'BarAssistantBot', false];
        yield 'exact match ignores wildcard group rules' => [$robots, 'https://example.com/everything', 'BarAssistantBot', true];
        yield 'wildcard group applies when no exact match' => [$robots, 'https://example.com/everything', 'OtherBot', false];
        yield 'wildcard group does not disallow secret for other bots' => [$robots, 'https://example.com/secret', 'OtherBot', true];
        yield 'product token match is case-insensitive' => [$robots, 'https://example.com/secret', 'barassistantbot', false];

        $merged = "User-agent: BotA\nUser-agent: BotB\nDisallow: /shared\n";

        yield 'consecutive user-agent lines merge for first token' => [$merged, 'https://example.com/shared', 'BotA', false];
        yield 'consecutive user-agent lines merge for second token' => [$merged, 'https://example.com/shared', 'BotB', false];

        $noMatch = "User-agent: OtherBot\nDisallow: /x\n";

        yield 'no matching group allows all' => [$noMatch, 'https://example.com/x', 'BarAssistantBot', true];
    }
}
