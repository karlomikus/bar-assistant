<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OAT;
use Kami\Cocktail\OpenAPI as BAO;
use Kami\Cocktail\Models\Calculator;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Resources\CalculatorResource;
use Kami\Cocktail\OpenAPI\Schemas\CalculatorRequest;
use Kami\Cocktail\OpenAPI\Schemas\CalculatorBlockRequest;
use BarAssistant\Application\Calculator\CalculatorService;
use Kami\Cocktail\Http\Resources\CalculatorResultResource;
use BarAssistant\Application\Calculator\DTO\SolveCalculator;
use BarAssistant\Application\Calculator\DTO\CreateCalculator;
use BarAssistant\Application\Calculator\DTO\UpdateCalculator;
use BarAssistant\Application\Calculator\DTO\CreateCalculatorBlock;
use Kami\Cocktail\Http\Requests\CalculatorRequest as CalculatorFormRequest;

class CalculatorController extends Controller
{
    #[OAT\Get(path: '/calculators', tags: ['Calculator'], operationId: 'listCalculators', description: 'Show a list of all calculators in a bar', summary: 'List calculators', parameters: [
        new BAO\Parameters\BarIdHeaderParameter(),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapItemsWithData(CalculatorResource::class),
    ])]
    public function index(): JsonResource
    {
        $calculators = Calculator::filterByBar()->with('blocks')->get();

        return CalculatorResource::collection($calculators);
    }

    #[OAT\Get(path: '/calculators/{id}', tags: ['Calculator'], operationId: 'showCalculator', description: 'Show a specific calculator', summary: 'Show calculator', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapObjectWithData(CalculatorResource::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function show(Request $request, int $id): JsonResource
    {
        $calculator = Calculator::with('blocks')->findOrFail($id);

        if ($request->user()->cannot('show', $calculator)) {
            abort(403);
        }

        return new CalculatorResource($calculator);
    }

    #[OAT\Post(path: '/calculators', tags: ['Calculator'], operationId: 'saveCalculator', description: 'Create a new calculator', summary: 'Create calculator', parameters: [
        new BAO\Parameters\BarIdHeaderParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(ref: BAO\Schemas\CalculatorRequest::class),
        ]
    ))]
    #[OAT\Response(response: 201, description: 'Successful response', headers: [
        new OAT\Header(header: 'Location', description: 'URL of the new resource', schema: new OAT\Schema(type: 'string')),
    ])]
    #[BAO\NotAuthorizedResponse]
    public function store(CalculatorService $calculatorService, CalculatorFormRequest $request): Response
    {
        if ($request->user()->cannot('create', Calculator::class)) {
            abort(403);
        }

        $requestSchema = CalculatorRequest::fromArray($request->all());

        $calculatorResult = $calculatorService->createCalculator(new CreateCalculator(
            barId: bar()->id,
            name: $requestSchema->name,
            description: $requestSchema->description,
            blocks: array_map(static function (CalculatorBlockRequest $block) {
                return new CreateCalculatorBlock(
                    label: $block->label,
                    variableName: $block->variableName,
                    value: $block->value,
                    type: $block->type->value,
                    settings: $block->settings->toArray(),
                    description: $block->description,
                    sort: $block->sort,
                );
            }, $requestSchema->blocks),
        ));

        return new Response(status: 201, headers: ['Location' => route('calculators.show', $calculatorResult->id, false)]);
    }

    #[OAT\Put(path: '/calculators/{id}', tags: ['Calculator'], operationId: 'updateCalculator', description: 'Update a specific calculator', summary: 'Update calculator', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(ref: BAO\Schemas\CalculatorRequest::class),
        ]
    ))]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function update(CalculatorService $calculatorService, CalculatorFormRequest $request, int $id): Response
    {
        $calculator = Calculator::findOrFail($id);

        if ($request->user()->cannot('edit', $calculator)) {
            abort(403);
        }

        $requestSchema = CalculatorRequest::fromArray($request->all());

        $calculatorService->updateCalculator(new UpdateCalculator(
            calculatorId: $id,
            name: $requestSchema->name,
            description: $requestSchema->description,
            blocks: array_map(static function (CalculatorBlockRequest $block) {
                return new CreateCalculatorBlock(
                    label: $block->label,
                    variableName: $block->variableName,
                    value: $block->value,
                    type: $block->type->value,
                    settings: $block->settings->toArray(),
                    description: $block->description,
                    sort: $block->sort,
                );
            }, $requestSchema->blocks),
        ));

        return new Response(status: 204);
    }

    #[OAT\Delete(path: '/calculators/{id}', tags: ['Calculator'], operationId: 'deleteCalculator', description: 'Delete a specific calculator', summary: 'Delete calculator', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function delete(CalculatorService $calculatorService, Request $request, int $id): Response
    {
        $calculator = Calculator::findOrFail($id);

        if ($request->user()->cannot('delete', $calculator)) {
            abort(403);
        }

        $calculatorService->deleteCalculator($calculator->id);

        return new Response(null, 204);
    }

    #[OAT\Post(path: '/calculators/{id}/solve', tags: ['Calculator'], operationId: 'solveCalculator', description: 'Solve calculator expressions. Takes a JSON body with the calculator input variables names as keys and their values as values and solves the defined evaluations. Results are objects with evaluation variable names as keys and solved expression result as values.', summary: 'Solve calculator', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(ref: BAO\Schemas\CalculatorSolveRequest::class),
        ]
    ))]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapObjectWithData(CalculatorResultResource::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function solve(CalculatorService $calculatorService, Request $request, int $id): CalculatorResultResource
    {
        $calculator = Calculator::findOrFail($id);

        if ($request->user()->cannot('show', $calculator)) {
            abort(403);
        }

        $result = $calculatorService->solveCalculator(new SolveCalculator(
            $id,
            $request->input('inputs')
        ));

        return new CalculatorResultResource($result);
    }
}
