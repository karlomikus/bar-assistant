<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Throwable;
use Kami\Cocktail\ZipUtils;
use Illuminate\Http\Response;
use Kami\Cocktail\Models\Bar;
use OpenApi\Attributes as OAT;
use Illuminate\Http\JsonResponse;
use Kami\Cocktail\OpenAPI as BAO;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Scraper\Manager;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Kami\Cocktail\External\Model\Schema;
use Kami\Cocktail\Http\Requests\ImportRequest;
use Kami\Cocktail\Http\Requests\ScrapeRequest;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\External\Import\FromJsonSchema;
use Kami\Cocktail\Http\Requests\ImportFileRequest;
use Kami\Cocktail\Http\Resources\CocktailResource;
use Kami\Cocktail\External\Import\DuplicateActionsEnum;

class ImportController extends Controller
{
    #[OAT\Post(path: '/import/cocktail', tags: ['Import'], summary: 'Import from recipe schema', parameters: [
        new BAO\Parameters\BarIdParameter(),
        new BAO\Parameters\BarIdHeaderParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(type: 'object', properties: [
                new OAT\Property(property: 'schema', ref: Schema::SCHEMA_URL),
                new OAT\Property(property: 'duplicate_actions', ref: DuplicateActionsEnum::class, example: 'none', description: 'How to handle duplicates. Cocktails are matched by lowercase name.'),
            ]),
        ]
    ))]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
        new BAO\WrapObjectWithData(BAO\Schemas\Cocktail::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    public function cocktail(ImportRequest $request, FromJsonSchema $importer): JsonResource
    {
        if ($request->user()->cannot('create', Cocktail::class)) {
            abort(403);
        }

        $source = $request->post('schema');
        $duplicateAction = DuplicateActionsEnum::from($request->post('duplicate_actions', 'none'));

        $cocktail = $importer->process($source, $request->user()->id, bar()->id, $duplicateAction);

        return new CocktailResource($cocktail);
    }

    #[OAT\Post(path: '/import/scrape', tags: ['Import'], summary: 'Scrape a recipe', description: 'Try to scrape a recipe from a website. Most of the well known recipe websites should work. Data returned is a valid JSON schema that you can import using import cocktail endpoint.', parameters: [
        new BAO\Parameters\BarIdParameter(),
        new BAO\Parameters\BarIdHeaderParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(type: 'object', properties: [
                new OAT\Property(property: 'source', type: 'string', example: 'https://www.example.com/recipe-url'),
            ]),
        ]
    ))]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
        new OAT\JsonContent(type: 'object', properties: [
            new OAT\Property(property: 'schema_version', type: 'string', example: 'draft2'),
            new OAT\Property(property: 'schema', ref: Schema::SCHEMA_URL),
            new OAT\Property(property: 'scraper_meta', type: 'object', additionalProperties: true),
        ]),
    ])]
    #[BAO\NotAuthorizedResponse]
    public function scrape(ScrapeRequest $request): JsonResponse|JsonResource
    {
        if ($request->user()->cannot('create', Cocktail::class)) {
            abort(403);
        }

        try {
            $scraper = Manager::scrape($request->post('source'));
        } catch (Throwable $e) {
            abort(400, $e->getMessage());
        }

        $dataToImport = $scraper->toArray();

        return response()->json([
            'data' => $dataToImport,
        ]);
    }

    #[OAT\Post(path: '/import/file', tags: ['Import'], summary: 'Import from zip file', requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\MediaType(mediaType: 'multipart/form-data', schema: new OAT\Schema(type: 'object', required: ['file'], properties: [
                new OAT\Property(property: 'file', type: 'string', format: 'binary', description: 'The zip file containing the data. Max 1GB.'),
                new OAT\Property(property: 'bar_id', type: 'integer', example: 1),
                new OAT\Property(property: 'duplicate_actions', ref: DuplicateActionsEnum::class, example: 'none', description: 'How to handle duplicates. Cocktails are matched by lowercase name.'),
            ])),
        ]
    ))]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[BAO\NotAuthorizedResponse]
    public function file(ImportFileRequest $request): Response
    {
        if ($request->user()->cannot('create', Cocktail::class)) {
            abort(403);
        }

        $zipFile = $request->file('file')->store('/', 'temp-uploads');
        $barId = $request->post('bar_id');
        $duplicateAction = DuplicateActionsEnum::from($request->post('duplicate_actions', 'none'));

        $bar = Bar::findOrFail($barId);
        if ($request->user()->cannot('edit', $bar)) {
            abort(403);
        }

        $unzippedFilesDisk = Storage::disk('temp');

        $zip = new ZipUtils();
        $zip->unzip(Storage::disk('temp-uploads')->path($zipFile));

        $importer = new FromJsonSchema(
            resolve(\Kami\Cocktail\Services\CocktailService::class),
            resolve(\Kami\Cocktail\Services\IngredientService::class),
            resolve(\Kami\Cocktail\Services\Image\ImageService::class),
            $bar->id,
        );

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            foreach ($unzippedFilesDisk->directories($zip->getDirName() . '/cocktails') as $diskDirPath) {
                $importer->process(
                    json_decode(file_get_contents($unzippedFilesDisk->path($diskDirPath . '/recipe.json')), true),
                    $request->user()->id,
                    $bar->id,
                    $duplicateAction,
                    $unzippedFilesDisk->path($diskDirPath),
                );
            }
        } catch (Throwable $e) {
            Log::error('Error importing from file: ' . $e->getMessage());
        } finally {
            $zip->cleanup();
            Storage::disk('temp-uploads')->delete($zipFile);
        }

        \Illuminate\Support\Facades\DB::commit();

        return new Response(null, 204);
    }
}
