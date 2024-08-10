<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Throwable;
use Symfony\Component\Yaml\Yaml;
use OpenApi\Attributes as OAT;
use Kami\Cocktail\OpenAPI as BAO;
use Illuminate\Http\JsonResponse;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Scraper\Manager;
use Kami\Cocktail\External\Import\FromArray;
use Kami\Cocktail\Http\Requests\ImportRequest;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Resources\CocktailResource;
use Kami\Cocktail\External\Import\DuplicateActionsEnum;

class ImportController extends Controller
{
    #[OAT\Post(path: '/import/cocktail', tags: ['Import'], summary: 'Import a cocktail', parameters: [
        new BAO\Parameters\BarIdParameter(),
        new OAT\Parameter(name: 'type', in: 'query', description: 'Type of import', required: true, schema: new OAT\Schema(type: 'string', enum: ['url', 'json', 'yaml', 'yml', 'collection'])),
        new OAT\Parameter(name: 'save', in: 'query', description: 'Save imported cocktails to the database', schema: new OAT\Schema(type: 'boolean')),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(type: 'object', properties: [
                new OAT\Property(property: 'source', type: 'string', example: 'https://www.example.com/recipe-url'),
                new OAT\Property(property: 'duplicate_actions', ref: DuplicateActionsEnum::class, example: '0'),
            ]),
        ]
    ))]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
        new BAO\WrapObjectWithData(BAO\Schemas\Cocktail::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    public function cocktail(ImportRequest $request, FromArray $arrayImporter): JsonResponse|JsonResource
    {
        if ($request->user()->cannot('create', Cocktail::class)) {
            abort(403);
        }

        $dataToImport = [];
        $type = $request->get('type', 'url');
        $save = $request->get('save', false);
        $source = $request->post('source');
        $duplicateAction = DuplicateActionsEnum::from((int) $request->post('duplicate_actions', '0'));

        if ($type === 'url') {
            $request->validate(['source' => 'url']);
            try {
                $scraper = Manager::scrape($source);
            } catch (Throwable $e) {
                abort(400, $e->getMessage());
            }

            $dataToImport = $scraper->toArray();
        }

        if ($type === 'json') {
            if (!is_array($source)) {
                if (!$source = json_decode($source, true)) {
                    abort(400, 'Unable to parse the JSON string');
                }
            }

            $dataToImport = $source;
        }

        if ($type === 'yaml' || $type === 'yml') {
            try {
                $dataToImport = Yaml::parse($source);
            } catch (Throwable) {
                abort(400, sprintf('Unable to parse the YAML string'));
            }
        }

        if ($save) {
            $cocktail = $arrayImporter->process($dataToImport, $request->user()->id, bar()->id);
            $cocktail->load(['ingredients.ingredient', 'images' => function ($query) {
                $query->orderBy('sort');
            }, 'tags', 'glass', 'ingredients.substitutes', 'method', 'createdUser', 'updatedUser', 'collections', 'utensils']);
            $dataToImport = new CocktailResource($cocktail);
        }

        return response()->json([
            'data' => $dataToImport
        ]);
    }
}
