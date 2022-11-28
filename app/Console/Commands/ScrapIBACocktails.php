<?php

namespace Kami\Cocktail\Console\Commands;

use Throwable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpKernel\HttpCache\Store;
use Symfony\Component\HttpClient\CachingHttpClient;

class ScrapIBACocktails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bar:scrap-iba-site';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrap IBA cocktails';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $store = new Store(storage_path('http_cache/'));
        $client = HttpClient::create();
        $client = new CachingHttpClient($client, $store);
        $browser = new HttpBrowser($client);

        $browser->request('GET', 'https://iba-world.com/category/iba-cocktails/');
        $crawler = new Crawler($browser->getResponse());

        $cocktailLinks = [];
        $crawler->filter('article.category-iba-cocktails')->each(function ($node) use (&$cocktailLinks) {
            $cocktailLinks[] = [
                'link' => $node->filter('.entry-title a')->first()->attr('href'),
                'image' => str_replace('-400x250', '', $node->filter('img')->first()->attr('src'))
            ];
        });

        $cocktails = [];
        foreach ($cocktailLinks as $cLink) {
            try {
                $browser->request('GET', $cLink['link']);
                $crawler = new Crawler($browser->getResponse());

                $cocktailName = $crawler->filter('h1.entry-title')->first()->text();
                $cocktailCategory = $crawler->filter('.et_pb_title_container .et_pb_title_meta_container')->first()->text();

                $this->info('Proccessing ' . $cocktailName);

                $ingredients = explode("\n", $crawler->filter('.blog-post-content p')->eq(0)->extract(['_text'])[0]);
                $instructions = $crawler->filter('.blog-post-content p')->eq(1)->extract(['_text']);
                $garnish = $crawler->filter('.blog-post-content p')->eq(2)->extract(['_text']);

                $content = file_get_contents($cLink['image']);
                file_put_contents(resource_path('data/images/' . basename($cLink['image'])), $content);

                $cocktails[] = [
                    'name' => $cocktailName,
                    'ingredients' => $ingredients,
                    'instructions' => $instructions,
                    'garnish' => $garnish,
                    'source' => $cLink['link'],
                    'image_src' => $cLink['image'],
                    'categories' => explode(', ', $cocktailCategory),
                ];
            } catch (Throwable $e) {
                Log::error('[SCRAPER] ' . $e->getMessage());
                continue;
            }
        }

        file_put_contents(resource_path('/data/iba_cocktails.json'), json_encode($cocktails, JSON_PRETTY_PRINT));

        return Command::SUCCESS;
    }
}
