<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Kami\Cocktail\Models\User;
use Illuminate\Http\JsonResponse;
use Kami\Cocktail\Search\MeilisearchActions;
use Illuminate\Support\Facades\Hash;
use Kami\Cocktail\Http\Requests\UserRequest;
use Kami\Cocktail\Http\Resources\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;

class UsersController extends Controller
{
    public function index(Request $request): JsonResource
    {
        if (!$request->user()->isAdmin()) {
            abort(403);
        }

        $users = User::orderBy('id')->get();

        return UserResource::collection($users);
    }

    public function show(Request $request, int $id): JsonResource
    {
        if (!$request->user()->isAdmin()) {
            abort(403);
        }

        $user = User::findOrFail($id);

        return new UserResource($user);
    }

    public function store(MeilisearchActions $search, UserRequest $request): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            abort(403);
        }

        $user = new User();
        $user->name = $request->post('name');
        $user->email = $request->post('email');
        $user->email_verified_at = now();
        $user->password = Hash::make($request->post('password'));
        $user->is_admin = (bool) $request->post('is_admin');
        $user->search_api_key = $search->getPublicApiKey();
        $user->save();

        return (new UserResource($user))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('users.show', $user->id));
    }

    public function update(int $id, UserRequest $request): JsonResource
    {
        if (!$request->user()->isAdmin() || $id === 1) {
            abort(403);
        }

        $user = User::findOrFail($id);
        $user->name = $request->post('name');
        $user->email = $request->post('email');
        $user->email_verified_at = now();
        $user->is_admin = (bool) $request->post('is_admin');

        if ($request->has('password')) {
            $user->password = Hash::make($request->post('password'));
        }

        $user->save();

        return new UserResource($user);
    }

    public function delete(Request $request, int $id): Response
    {
        if (!$request->user()->isAdmin() || $id === 1) {
            abort(403);
        }

        User::findOrFail($id)->delete();

        return response(null, 204);
    }
}
