<?php

namespace Kami\Cocktail\Console\Commands;

use Illuminate\Console\Command;
use Kami\Cocktail\Models\Cocktail;
use Codewithkyrian\ChromaDB\ChromaDB;
use Codewithkyrian\ChromaDB\Embeddings\HuggingFaceEmbeddingServerFunction;

class Aitest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bar:aitest';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $chroma = ChromaDB::factory()
            ->withHost('http://localhost')
            ->withPort(8086)
            ->connect();

        $embeddingFunction = new HuggingFaceEmbeddingServerFunction('http://localhost:8085');

        $collection = $chroma->createCollection('test-collection4', embeddingFunction: $embeddingFunction);

        $cocktails = Cocktail::with('ingredients.ingredient')->limit(10)->get();
        foreach ($cocktails as $cocktail) {
            $collection->add(
                ids: [$cocktail->slug],
                documents: [$cocktail->name . ' ' . $cocktail->description],
                metadatas: [
                    ['ingredients' => $cocktail->getShortIngredients()->implode(', '),]
                ]
            );
        }

        return 0;
    }
}
