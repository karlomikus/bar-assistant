<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Throwable;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use OpenApi\Attributes as OAT;
use Illuminate\Http\JsonResponse;
use Kami\Cocktail\OpenAPI as BAO;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Scraper\Manager;
use Kami\Cocktail\Models\Ingredient;
use Illuminate\Support\Facades\Storage;
use Kami\Cocktail\External\Model\Schema;
use Illuminate\Support\Facades\Validator;
use Kami\Cocktail\Services\CocktailService;
use Kami\Cocktail\Services\IngredientService;
use Kami\Cocktail\Http\Requests\ImportRequest;
use Kami\Cocktail\Http\Requests\ScrapeRequest;
use Kami\Cocktail\Services\Image\ImageService;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Jobs\StartIngredientCSVImport;
use Kami\Cocktail\External\Import\FromJsonSchema;
use Kami\Cocktail\Http\Resources\CocktailResource;
use Kami\Cocktail\External\Import\DuplicateActionsEnum;

class ImportController extends Controller
{
    #[OAT\Post(path: '/import/cocktail', tags: ['Import'], operationId: 'importCocktail', summary: 'Import recipe', description: 'Import a recipe from a JSON structure that follows Bar Assistant recipe JSON schema. Supported schemas include [Draft 2](https://barassistant.app/cocktail-02.schema.json) and [Draft 1](https://barassistant.app/cocktail-01.schema.json).', parameters: [
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
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapObjectWithData(BAO\Schemas\Cocktail::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\RateLimitResponse]
    public function cocktail(ImportRequest $request): JsonResource
    {
        if ($request->user()->cannot('create', Cocktail::class)) {
            abort(403);
        }

        $source = $request->input('source');
        $duplicateAction = $request->enum('duplicate_actions', DuplicateActionsEnum::class);

        $importer = new FromJsonSchema(
            resolve(CocktailService::class),
            resolve(IngredientService::class),
            resolve(ImageService::class),
            bar()->id,
            $request->user()->id,
        );

        if (is_array($source)) {
            $cocktail = $importer->process($source, $duplicateAction);
        } else {
            $cocktail = $importer->process(json_decode($source, true), $duplicateAction);
        }

        return new CocktailResource($cocktail);
    }

    #[OAT\Post(path: '/import/scrape', tags: ['Import'], operationId: 'scrapeRecipe', summary: 'Scrape recipe', description: 'Try to scrape a recipe from a website. Most of the well known recipe websites should work. Data returned is a valid JSON schema that you can import using import cocktail endpoint.', parameters: [
        new BAO\Parameters\BarIdHeaderParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(type: 'object', properties: [
                new OAT\Property(property: 'source', type: 'string', example: 'https://www.example.com/recipe-url'),
            ]),
        ]
    ))]
    #[BAO\SuccessfulResponse(content: [
        new OAT\JsonContent(type: 'object', properties: [
            new OAT\Property(property: 'data', type: 'object', properties: [
                new OAT\Property(property: 'schema_version', type: 'string', example: 'draft2'),
                new OAT\Property(property: 'schema', ref: Schema::SCHEMA_URL),
                new OAT\Property(property: 'scraper_meta', type: 'array', items: new OAT\Items(type: 'object', properties: [
                    new OAT\Property(property: '_id', type: 'string'),
                    new OAT\Property(property: 'source', type: 'string'),
                ], required: ['_id', 'source'])),
            ], required: ['schema_version', 'schema', 'scraper_meta']),
        ], required: ['data']),
    ])]
    #[BAO\NotAuthorizedResponse]
    public function scrape(ScrapeRequest $request): JsonResponse
    {
        if ($request->user()->cannot('create', Cocktail::class)) {
            abort(403);
        }

        try {
            $scraper = Manager::scrape($request->input('source'));
        } catch (Throwable $e) {
            abort(400, $e->getMessage());
        }

        $dataToImport = $scraper->toArray();

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

            $file = $request->source->store('', 'temp-uploads');
        } else {
            $file = Str::random(10) . '.csv';
            $csv = $request->getContent();
            if (!Storage::disk('temp-uploads')->put($file, $csv)) {
                abort(500, 'Unable to store uploaded file');
            }
        }

        StartIngredientCSVImport::dispatch(bar()->id, $request->user()->id, $file);

        return response()->json(status: 204);
    }

    // #[OAT\Post(path: '/import/file', tags: ['Import'], summary: 'Import from zip file', requestBody: new OAT\RequestBody(
    //     required: true,
    //     content: [
    //         new OAT\MediaType(mediaType: 'multipart/form-data', schema: new OAT\Schema(type: 'object', required: ['file'], properties: [
    //             new OAT\Property(property: 'file', type: 'string', format: 'binary', description: 'The zip file containing the data. Max 1GB.'),
    //             new OAT\Property(property: 'bar_id', type: 'integer', example: 1),
    //             new OAT\Property(property: 'duplicate_actions', ref: DuplicateActionsEnum::class, example: 'none', description: 'How to handle duplicates. Cocktails are matched by lowercase name.'),
    //         ])),
    //     ]
    // ))]
    // #[OAT\Response(response: 204, description: 'Successful response')]
    // #[BAO\NotAuthorizedResponse]
    // public function file(ImportFileRequest $request): Response
    // {
    //     if ($request->user()->cannot('create', Cocktail::class)) {
    //         abort(403);
    //     }

    //     $zipFile = $request->file('file')->store('/', 'temp-uploads');
    //     $barId = $request->post('bar_id');
    //     $duplicateAction = DuplicateActionsEnum::from($request->post('duplicate_actions', 'none'));

    //     $bar = Bar::findOrFail($barId);
    //     if ($request->user()->cannot('edit', $bar)) {
    //         abort(403);
    //     }

    //     $unzippedFilesDisk = Storage::disk('temp');

    //     $zip = new ZipUtils();
    //     $zip->unzip(Storage::disk('temp-uploads')->path($zipFile));

    //     // $importer = new FromJsonSchema(
    //     //     resolve(\Kami\Cocktail\Services\CocktailService::class),
    //     //     resolve(\Kami\Cocktail\Services\IngredientService::class),
    //     //     resolve(\Kami\Cocktail\Services\Image\ImageService::class),
    //     //     $bar->id,
    //     // );

    //     // \Illuminate\Support\Facades\DB::beginTransaction();
    //     // try {
    //     //     foreach ($unzippedFilesDisk->directories($zip->getDirName() . '/cocktails') as $diskDirPath) {
    //     //         $importer->process(
    //     //             json_decode(file_get_contents($unzippedFilesDisk->path($diskDirPath . '/recipe.json')), true),
    //     //             $request->user()->id,
    //     //             $bar->id,
    //     //             $duplicateAction,
    //     //             $unzippedFilesDisk->path($diskDirPath),
    //     //         );
    //     //     }
    //     // } catch (Throwable $e) {
    //     //     Log::error('Error importing from file: ' . $e->getMessage());
    //     // } finally {
    //     //     $zip->cleanup();
    //     //     Storage::disk('temp-uploads')->delete($zipFile);
    //     // }

    //     \Illuminate\Support\Facades\DB::commit();

    //     return new Response(null, 204);
    // }
}
