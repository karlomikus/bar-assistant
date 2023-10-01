<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Kami\Cocktail\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Kami\Cocktail\Http\Requests\UserRequest;
use Kami\Cocktail\Http\Resources\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;

class UsersController extends Controller
{
    public function index(Request $request): JsonResource
    {
        if ($request->user()->cannot('list', User::class)) {
            abort(403);
        }

        $users = User::orderBy('name')
            ->select('users.*')
            ->join('bar_memberships', 'bar_memberships.user_id', '=', 'users.id')
            ->where('bar_memberships.bar_id', bar()->id)
            ->get();

        return UserResource::collection($users);
    }

    public function show(Request $request, int $id): JsonResource
    {
        $user = User::select('users.*')
            ->join('bar_memberships', 'bar_memberships.user_id', '=', 'users.id')
            ->where('bar_memberships.bar_id', bar()->id)
            ->where('bar_memberships.user_id', $id)
            ->firstOrFail();

        if ($request->user()->cannot('show', $user)) {
            abort(403);
        }

        return new UserResource($user);
    }

    public function store(UserRequest $request): JsonResponse
    {
        if ($request->user()->cannot('create', User::class)) {
            abort(403);
        }

        $roleId = $request->post('role_id');
        $email = $request->post('email');

        $user = User::where('email', $email)->first();
        if ($user === null) {
            $user = new User();
            $user->name = $request->post('name');
            $user->email = $request->post('email');
            $user->password = Hash::make($request->post('password'));
            $user->save();
        }

        bar()->users()->save($user, ['user_role_id' => $roleId]);

        return (new UserResource($user))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('users.show', $user->id));
    }

    public function update(int $id, UserRequest $request): JsonResource
    {
        $user = User::findOrFail($id);

        if ($request->user()->cannot('edit', $user)) {
            abort(403);
        }

        $user->name = $request->post('name');

        if ($request->has('role_id') && $user->isBarAdmin(bar()->id)) {
            $barMembership = $user->getBarMembership(bar()->id);
            $barMembership->user_role_id = $request->post('role_id');
            $barMembership->save();
        }

        $user->save();

        return new UserResource($user);
    }

    public function delete(Request $request, int $id): Response
    {
        $user = User::findOrFail($id);

        if ($request->user()->cannot('delete', $user)) {
            abort(403);
        }

        $user->tokens()->delete();
        $user->delete();

        return response(null, 204);
    }
}
