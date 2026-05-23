<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Throwable;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OAT;
use Illuminate\Http\JsonResponse;
use Kami\Cocktail\OpenAPI as BAO;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Scraper\Manager;
use Kami\Cocktail\Models\Ingredient;
use Illuminate\Support\Facades\Storage;
use Kami\Cocktail\External\Model\Schema;
use Illuminate\Support\Facades\Validator;
use Kami\Cocktail\External\Import\FromSchema;
use Kami\Cocktail\Http\Requests\ImportRequest;
use Kami\Cocktail\Http\Requests\ScrapeRequest;
use Kami\Cocktail\Jobs\StartIngredientCSVImport;
use Kami\Cocktail\External\Import\DuplicateActionsEnum;

class ImportController extends Controller
{
    #[OAT\Post(path: '/import/cocktail', tags: ['Import'], operationId: 'importCocktail', summary: 'Import recipe', description: 'Import a recipe from a JSON structure that follows Bar Assistant recipe JSON schema v4: https://barassistant.app/cocktail-04.schema.json', parameters: [
        new BAO\Parameters\BarIdHeaderParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(type: 'object', properties: [
                new OAT\Property(property: 'source', type: 'string', description: 'Valid JSON structure to import.'),
                new OAT\Property(property: 'duplicate_actions', ref: DuplicateActionsEnum::class, example: 'none', description: 'How to handle duplicates. Cocktails are matched by lowercase name.'),
            ]),
        ]
    ))]
    #[OAT\Response(response: 201, description: 'Successful response', headers: [
        new OAT\Header(header: 'Location', description: 'URL of the new resource', schema: new OAT\Schema(type: 'string')),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\RateLimitResponse]
    public function cocktail(FromSchema $importer, ImportRequest $request): Response
    {
        if ($request->user()->cannot('create', Cocktail::class)) {
            abort(403);
        }

        $source = $request->input('source');
        $duplicateAction = $request->enum('duplicate_actions', DuplicateActionsEnum::class);

        if (!is_array($source)) {
            $source = json_decode((string) $source, true, flags: JSON_THROW_ON_ERROR);
        }

        $schema = Schema::fromSchema4Array($source);
        $cocktail = $importer->process(bar()->id, $request->user()->id, $schema, $duplicateAction);

        return new Response(status: 201, headers: ['Location' => route('cocktails.show', $cocktail->id, false)]);
    }

    #[OAT\Post(path: '/import/scrape', tags: ['Import'], operationId: 'scrapeRecipe', summary: 'Scrape recipe', description: 'Try to scrape a recipe from a website. Most of the well known recipe websites should work. Data returned is a valid JSON schema that you can import using import cocktail endpoint.', parameters: [
        new BAO\Parameters\BarIdHeaderParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(type: 'object', properties: [
                new OAT\Property(property: 'source', type: 'string', example: 'https://www.example.com/recipe-url'),
                new OAT\Property(property: 'html_content', type: 'string', nullable: true, example: '<p>HTML content</p>'),
            ], required: ['source']),
        ]
    ))]
    #[BAO\SuccessfulResponse(content: [
        new OAT\JsonContent(type: 'object', properties: [
            new OAT\Property(property: 'data', type: 'object', properties: [
                new OAT\Property(property: 'schema_version', type: 'string', example: 'schema4'),
                new OAT\Property(property: 'schema', ref: Schema::SCHEMA_URL),
                new OAT\Property(property: 'scraper_meta', type: 'array', items: new OAT\Items(type: 'object', properties: [
                    new OAT\Property(property: 'ingredient_name', type: 'string'),
                    new OAT\Property(property: 'source', type: 'string'),
                ], required: ['ingredient_name', 'source'])),
            ], required: ['schema_version', 'schema', 'scraper_meta']),
        ], required: ['data']),
    ])]
    #[BAO\NotAuthorizedResponse]
    public function scrape(ScrapeRequest $request): JsonResponse
    {
        if ($request->user()->cannot('create', Cocktail::class)) {
            abort(403);
        }

        $content = $request->input('html_content', null);

        try {
            $scraper = Manager::scrape($request->input('source'), $content);
            $dataToImport = $scraper->toArray();
        } catch (Throwable $e) {
            abort(400, $e->getMessage());
        }

        return response()->json([
            'data' => $dataToImport,
        ]);
    }

    #[OAT\Post(path: '/import/ingredients', tags: ['Import'], operationId: 'importIngredients', summary: 'Import ingredients', description: 'Import ingredients from a CSV source', parameters: [
        new BAO\Parameters\BarIdHeaderParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\MediaType(mediaType: 'text/csv'),
            new OAT\MediaType(mediaType: 'multipart/form-data', schema: new OAT\Schema(type: 'object', required: ['source'], properties: [
                new OAT\Property(property: 'source', type: 'string', format: 'binary', description: 'CSV file'),
            ])),
        ]
    ))]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[BAO\NotAuthorizedResponse]
    public function ingredients(Request $request): JsonResponse
    {
        if ($request->user()->cannot('bulkImport', Ingredient::class)) {
            abort(403);
        }

        if ($request->hasFile('source')) {
            Validator::make($request->all(), [
                'source' => 'required|file|mimes:csv|max:1048576',
            ])->validate();

            $file = $request->source->store('', 'temp');
        } else {
            $file = Str::random(10) . '.csv';
            $csv = $request->getContent();
            if (!Storage::disk('temp')->put($file, $csv)) {
                abort(500, 'Unable to store uploaded file');
            }
        }

        StartIngredientCSVImport::dispatch(bar()->id, $request->user()->id, $file);

        return response()->json(status: 204);
    }
}
