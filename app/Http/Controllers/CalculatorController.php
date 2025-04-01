<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OAT;
use Illuminate\Http\JsonResponse;
use Kami\Cocktail\OpenAPI as BAO;
use Kami\Cocktail\Models\Calculator;
use Kami\Cocktail\Models\CalculatorBlock;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Resources\CalculatorResource;
use Kami\Cocktail\OpenAPI\Schemas\CalculatorRequest;
use Kami\Cocktail\Models\ValueObjects\CalculatorResult;
use Kami\Cocktail\OpenAPI\Schemas\CalculatorSolveRequest;
use Kami\Cocktail\Http\Resources\CalculatorResultResource;
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
    #[OAT\Response(response: 201, description: 'Successful response', content: [
        new BAO\WrapObjectWithData(CalculatorResource::class),
    ], headers: [
        new OAT\Header(header: 'Location', description: 'URL of the new resource', schema: new OAT\Schema(type: 'string')),
    ])]
    #[BAO\NotAuthorizedResponse]
    public function store(CalculatorFormRequest $request): JsonResponse
    {
        if ($request->user()->cannot('create', Calculator::class)) {
            abort(403);
        }

        $requestSchema = CalculatorRequest::fromArray($request->all());

        $calculator = new Calculator();
        $calculator->name = $requestSchema->name;
        $calculator->description = $requestSchema->description;
        $calculator->bar_id = bar()->id;
        $calculator->save();

        foreach ($requestSchema->blocks as $block) {
            $calculatorBlock = new CalculatorBlock();
            $calculatorBlock->type = $block->type;
            $calculatorBlock->label = $block->label;
            $calculatorBlock->variable_name = $block->variableName;
            $calculatorBlock->value = $block->value;
            $calculatorBlock->sort = $block->sort;
            $calculatorBlock->description = $block->description;
            $calculatorBlock->calculator_id = $calculator->id;
            $calculatorBlock->settings = $block->settings->toArray();
            $calculatorBlock->save();
        }

        return (new CalculatorResource($calculator))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('calculators.show', $calculator->id));
    }

    #[OAT\Put(path: '/calculators/{id}', tags: ['Calculator'], operationId: 'updateCalculator', description: 'Update a specific calculator', summary: 'Update calculator', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(ref: BAO\Schemas\CalculatorRequest::class),
        ]
    ))]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapObjectWithData(CalculatorResource::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function update(CalculatorFormRequest $request, int $id): JsonResource
    {
        $calculator = Calculator::findOrFail($id);

        if ($request->user()->cannot('edit', $calculator)) {
            abort(403);
        }

        $requestSchema = CalculatorRequest::fromArray($request->all());

        $calculator->name = $requestSchema->name;
        $calculator->description = $requestSchema->description;
        $calculator->updated_at = now();
        $calculator->save();

        $calculator->blocks()->delete();
        foreach ($requestSchema->blocks as $block) {
            $calculatorBlock = new CalculatorBlock();
            $calculatorBlock->type = $block->type;
            $calculatorBlock->label = $block->label;
            $calculatorBlock->variable_name = $block->variableName;
            $calculatorBlock->value = $block->value;
            $calculatorBlock->sort = $block->sort;
            $calculatorBlock->description = $block->description;
            $calculatorBlock->calculator_id = $calculator->id;
            $calculatorBlock->settings = $block->settings->toArray();
            $calculatorBlock->save();
        }

        return new CalculatorResource($calculator);
    }

    #[OAT\Delete(path: '/calculators/{id}', tags: ['Calculator'], operationId: 'deleteCalculator', description: 'Delete a specific calculator', summary: 'Delete calculator', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function delete(Request $request, int $id): Response
    {
        $calculator = Calculator::findOrFail($id);

        if ($request->user()->cannot('delete', $calculator)) {
            abort(403);
        }

        $calculator->delete();

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
        new BAO\WrapObjectWithData(CalculatorResult::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function solve(Request $request, int $id): CalculatorResultResource
    {
        $calculator = Calculator::with('blocks')->findOrFail($id);

        if ($request->user()->cannot('show', $calculator)) {
            abort(403);
        }

        $userInputs = CalculatorSolveRequest::fromArray($request->all());
        $calculatorResult = $calculator->solve($userInputs);

        return new CalculatorResultResource($calculatorResult);
    }
}
