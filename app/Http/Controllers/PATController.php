<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OAT;
use Kami\Cocktail\OpenAPI as BAO;
use Illuminate\Support\Facades\App;
use Kami\Cocktail\Models\AbilityEnum;
use Kami\Cocktail\Http\Requests\PATRequest;
use Kami\Cocktail\Http\Resources\PATResource;
use Kami\Cocktail\Models\PersonalAccessToken;
use Kami\Cocktail\Http\Resources\TokenResource;
use Illuminate\Http\Resources\Json\JsonResource;

class PATController extends Controller
{
    #[OAT\Get(path: '/tokens', tags: ['Tokens'], summary: 'Show a list of tokens')]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
        new BAO\WrapItemsWithData(BAO\Schemas\PersonalAccessToken::class),
    ])]
    public function index(Request $request): JsonResource
    {
        // Shows a ton of tokens in demo which is not really useful
        if (App::environment('demo')) {
            return PATResource::collection([]);
        }

        $tokens = $request
            ->user()
            ->tokens()
            ->orderBy('created_at', 'desc')
            ->get();

        return PATResource::collection($tokens);
    }

    #[OAT\Post(path: '/tokens', tags: ['Tokens'], summary: 'Create new personal access token', requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(ref: BAO\Schemas\PersonalAccessTokenRequest::class),
        ]
    ))]
    #[OAT\Response(response: 201, description: 'Successful response', content: [
        new BAO\WrapObjectWithData(BAO\Schemas\Token::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    public function store(PATRequest $request): TokenResource
    {
        if ($request->user()->cannot('create', PersonalAccessToken::class)) {
            abort(403);
        }

        $expiresAt = $request->date('expires_at');

        $abilities = array_filter($request->input('abilities', []), fn ($inputAbility) => AbilityEnum::tryFrom($inputAbility) !== null);
        if (count($abilities) === 0) {
            abort(400, 'Unsupported abilities given, valid abilties include: ' . implode(', ', array_map(fn (AbilityEnum $ability) => $ability->value, AbilityEnum::cases())));
        }

        $token = $request->user()->createToken(
            $request->input('name', 'user_generated'),
            $abilities,
            $expiresAt
        );

        return new TokenResource($token);
    }

    #[OAT\Delete(path: '/tokens/{id}', tags: ['Tokens'], summary: 'Revoke personal access token', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function delete(Request $request, int $id): Response
    {
        $token = PersonalAccessToken::findOrFail($id);

        if ($request->user()->cannot('delete', $token)) {
            abort(403);
        }

        $token->delete();

        return new Response(null, 204);
    }
}
