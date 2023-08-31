<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Kami\Cocktail\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Kami\Cocktail\Models\UserRoleEnum;
use Kami\Cocktail\Http\Requests\UserRequest;
use Kami\Cocktail\Http\Resources\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;

class UsersController extends Controller
{
    public function index(Request $request): JsonResource
    {
        if (!$request->user()->isBarAdmin(bar()->id)) {
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
        $user = User::findOrFail($id);

        if (!$request->user()->isBarAdmin(bar()->id) || !$user->hasBarMembership(bar()->id)) {
            abort(403);
        }

        return new UserResource($user);
    }

    public function store(UserRequest $request): JsonResponse
    {
        if (!$request->user()->isBarAdmin(bar()->id)) {
            abort(403);
        }

        $roleId = $request->post('role_id', (string) UserRoleEnum::General->value);

        $user = new User();
        $user->name = $request->post('name');
        $user->email = $request->post('email');
        $user->email_verified_at = now();
        $user->password = Hash::make($request->post('password'));
        $user->save();

        bar()->users()->save($user, ['user_role_id' => $roleId]);

        return (new UserResource($user))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('users.show', $user->id));
    }

    public function update(int $id, UserRequest $request): JsonResource
    {
        $user = User::findOrFail($id);

        if (!$request->user()->isBarAdmin(bar()->id) || !$user->hasBarMembership(bar()->id)) {
            abort(403);
        }

        $user->name = $request->post('name');
        $user->email = $request->post('email');
        $user->email_verified_at = now();

        if ($request->has('password')) {
            $user->password = Hash::make($request->post('password'));
        }

        if ($request->has('role_id')) {
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

        if (!$request->user()->isBarAdmin(bar()->id) || !$user->hasBarMembership(bar()->id)) {
            abort(403);
        }

        $user->delete();

        return response(null, 204);
    }
}
