<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use Kami\Cocktail\Http\Requests\PATRequest;
use Kami\Cocktail\Http\Resources\PATResource;
use Kami\Cocktail\Models\PersonalAccessToken;
use Kami\Cocktail\Http\Resources\TokenResource;
use Illuminate\Http\Resources\Json\JsonResource;

class PATController extends Controller
{
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

    public function store(PATRequest $request): TokenResource
    {
        if ($request->user()->cannot('create', PersonalAccessToken::class)) {
            abort(403);
        }

        $expiresAt = $request->post('expires_at');
        if ($expiresAt) {
            $expiresAt = Carbon::parse($expiresAt);
        }

        $allowedAbilities = ['cocktails.read', 'cocktails.write', 'ingredients.read', 'ingredients.write'];
        $abilities = array_filter($request->post('abilities', []), fn ($inputAbility) => in_array($inputAbility, $allowedAbilities, true));
        if (count($abilities) === 0) {
            abort(400, 'Unknown abilities given, valid abilties include: ' . implode(', ', $allowedAbilities));
        }

        $token = $request->user()->createToken(
            $request->post('name', 'user_generated'),
            $abilities,
            $expiresAt
        );

        return new TokenResource($token);
    }

    public function delete(Request $request, int $id): Response
    {
        $token = PersonalAccessToken::findOrFail($id);

        if ($request->user()->cannot('delete', $token)) {
            abort(403);
        }

        $token->delete();

        return response(null, 204);
    }
}
