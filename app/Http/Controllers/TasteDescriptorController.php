<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use OpenApi\Attributes as OAT;
use Kami\Cocktail\OpenAPI as BAO;
use Kami\Cocktail\Models\TasteDescriptor;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Resources\TasteDescriptorResource;

class TasteDescriptorController extends Controller
{
    #[OAT\Get(path: '/taste-descriptors', tags: ['Taste Descriptors'], operationId: 'listTasteDescriptors', description: 'List taste descriptors for the current bar', summary: 'List taste descriptors', parameters: [
        new BAO\Parameters\BarIdHeaderParameter(),
        new OAT\Parameter(name: 'filter', in: 'query', description: 'Filter descriptors by attributes.', explode: true, style: 'deepObject', schema: new OAT\Schema(type: 'object', properties: [
            new OAT\Property(property: 'name', type: 'string', description: 'Filter by descriptor name (case-insensitive partial match)'),
        ])),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new OAT\JsonContent(properties: [
            new OAT\Property(property: 'data', type: 'array', items: new OAT\Items(ref: TasteDescriptorResource::class)),
        ]),
    ])]
    #[BAO\NotAuthorizedResponse]
    public function index(Request $request): JsonResource
    {
        $query = TasteDescriptor::query()->filterByBar();

        $name = $request->input('filter.name');
        if (is_string($name) && $name !== '') {
            $query->whereRaw('LOWER(name) LIKE ?', ['%' . mb_strtolower($name) . '%']);
        }

        $descriptors = $query->orderByRaw('LOWER(name) ASC')->get();

        return TasteDescriptorResource::collection($descriptors);
    }
}
