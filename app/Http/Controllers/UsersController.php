<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use BarAssistant\Application\Bar\DTO\CreateMemberRequest;
use BarAssistant\Application\Bar\MemberService;
use BarAssistant\Application\User\UserService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Kami\Cocktail\Models\User;
use OpenApi\Attributes as OAT;
use Illuminate\Http\JsonResponse;
use Kami\Cocktail\OpenAPI as BAO;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Kami\Cocktail\Mail\AccountDeleted;
use Kami\Cocktail\Mail\ConfirmAccount;
use Kami\Cocktail\Http\Requests\UserRequest;
use Kami\Cocktail\Http\Resources\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\OpenAPI\Schemas\RegisterRequest;
use Kami\Cocktail\Services\Auth\RegisterUserService;

class UsersController extends Controller
{
    #[OAT\Get(path: '/users', tags: ['Users'], operationId: 'listUsers', description: 'Show a list of all users in a bar', summary: 'List users', parameters: [
        new BAO\Parameters\BarIdHeaderParameter(),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapItemsWithData(UserResource::class),
    ])]
    public function index(Request $request): JsonResource
    {
        if ($request->user()->cannot('list', User::class)) {
            abort(403);
        }

        $users = User::orderBy('name')
            ->select('users.*')
            ->join('bar_memberships', 'bar_memberships.user_id', '=', 'users.id')
            ->where('bar_memberships.bar_id', bar()->id)
            ->get()
            ->load('memberships.role');

        return UserResource::collection($users);
    }

    #[OAT\Get(path: '/users/{id}', tags: ['Users'], operationId: 'showUser', description: 'Show a single user', summary: 'Show user', parameters: [
        new BAO\Parameters\BarIdHeaderParameter(),
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapObjectWithData(UserResource::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
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

    #[OAT\Post(path: '/users', tags: ['Users'], operationId: 'saveUser', description: 'Create a new user', summary: 'Create user', parameters: [
        new BAO\Parameters\BarIdHeaderParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(ref: BAO\Schemas\UserRequest::class),
        ]
    ))]
    #[OAT\Response(response: 201, description: 'Successful response', headers: [
        new OAT\Header(header: 'Location', description: 'URL of the new resource', schema: new OAT\Schema(type: 'string')),
    ])]
    #[BAO\NotAuthorizedResponse]
    public function store(RegisterUserService $registerService, MemberService $memberService, UserRequest $request): Response
    {
        if ($request->user()->cannot('create', User::class)) {
            abort(403);
        }

        $roleId = (int) $request->post('role_id');
        $email = $request->post('email');

        $user = User::where('email', $email)->first();
        if ($user === null) {
            $user = $registerService->register(RegisterRequest::fromIlluminateRequest($request));
        }

        $memberService->addMemberToBar(new CreateMemberRequest(userId: $user->id, barId: bar()->id, roleId: $roleId));

        return new Response(status: 204, headers: ['Location' => route('users.show', $user->id, false)]);
    }

    #[OAT\Put(path: '/users/{id}', tags: ['Users'], operationId: 'updateUser', description: 'Update a single user', summary: 'Update user', parameters: [
        new BAO\Parameters\BarIdHeaderParameter(),
        new BAO\Parameters\DatabaseIdParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(ref: BAO\Schemas\UserRequest::class),
        ]
    ))]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapObjectWithData(UserResource::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function update(int $id, UserRequest $request): JsonResource
    {
        $user = User::findOrFail($id);

        if ($request->user()->cannot('edit', $user)) {
            abort(403);
        }

        $user->name = $request->input('name');

        if ($request->has('role_id') && $request->user()->isBarAdmin(bar()->id)) {
            $barMembership = $user->getBarMembership(bar()->id);
            $barMembership->user_role_id = $request->post('role_id');
            $barMembership->save();
        }

        $user->save();

        return new UserResource($user);
    }

    #[OAT\Delete(path: '/users/{id}', tags: ['Users'], operationId: 'deleteUser', description: 'Delete a single user', summary: 'Delete user', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function delete(Request $request, int $id): Response
    {
        $user = User::findOrFail($id);

        if ($request->user()->cannot('delete', $user)) {
            abort(403);
        }

        $email = $user->email;

        if ($user->subscription()) {
            $user->subscription()->cancelNow();
        }

        $user->tokens()->delete();
        $user->makeAnonymous();
        $user->save();

        Mail::to($email)->queue(new AccountDeleted());

        return new Response(null, 204);
    }
}
