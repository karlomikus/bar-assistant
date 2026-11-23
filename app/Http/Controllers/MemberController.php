<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Kami\Cocktail\Models\User;
use OpenApi\Attributes as OAT;
use Kami\Cocktail\OpenAPI as BAO;
use Kami\Cocktail\Models\BarMembership;
use Kami\Cocktail\Http\Requests\UserRequest;
use BarAssistant\Application\Bar\MemberService;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Resources\MemberResource;
use Kami\Cocktail\Http\Requests\UpdateMemberRequest;
use BarAssistant\Application\Bar\DTO\CreateMemberRequest;
use BarAssistant\Application\Bar\DTO\RemoveMemberRequest;
use BarAssistant\Application\Bar\DTO\ChangeMemberRoleRequest;

class MemberController extends Controller
{
    #[OAT\Get(
        path: '/members',
        tags: ['Members'],
        operationId: 'listMembers',
        description: 'Show a list of all members in the bar. This endpoint is only accessible for bar admins.',
        summary: 'List members',
        parameters: [
            new BAO\Parameters\BarIdHeaderParameter(),
        ]
    )]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapItemsWithData(MemberResource::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    public function index(Request $request): JsonResource
    {
        $user = $request->user();
        if ($user === null || $user->cannot('list', BarMembership::class)) {
            abort(403);
        }

        $members = bar()
            ->memberships()
            ->join('users', 'users.id', '=', 'bar_memberships.user_id')
            ->orderBy('users.name')
            ->get()
            ->load(['user', 'role']);

        return MemberResource::collection($members);
    }

    #[OAT\Get(path: '/members/{id}', tags: ['Members'], operationId: 'showMember', description: 'Show member information', summary: 'Show member', parameters: [
        new BAO\Parameters\BarIdHeaderParameter(),
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapObjectWithData(MemberResource::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function show(Request $request, int $id): JsonResource
    {
        $membership = bar()
            ->memberships()
            ->with(['user', 'role'])
            ->where('bar_memberships.user_id', $id)
            ->firstOrFail();

        $user = $request->user();
        if ($user === null || $user->cannot('show', $membership)) {
            abort(403);
        }

        return new MemberResource($membership);
    }

    #[OAT\Post(path: '/members', tags: ['Members'], operationId: 'saveMember', description: 'Create a new member', summary: 'Create member', parameters: [
        new BAO\Parameters\BarIdHeaderParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(ref: BAO\Schemas\UserRequest::class),
        ]
    ))]
    #[OAT\Response(response: 201, description: 'Successful response', headers: [
        new OAT\Header(header: 'Location', description: 'URL of the new resource', schema: new OAT\Schema(type: 'string', format: 'uri-reference')),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    #[BAO\ValidationFailedResponse]
    public function store(MemberService $memberService, UserRequest $request): Response
    {
        $authenticatedUser = $request->user();
        if ($authenticatedUser === null || $authenticatedUser->cannot('create', BarMembership::class)) {
            abort(403);
        }

        $roleId = $request->integer('role_id');
        $email = $request->string('email')->toString();

        $user = User::where('email', $email)->first();
        if ($user === null) {
            abort(404);
        }

        $memberService->addMemberToBar(new CreateMemberRequest(userId: $user->id, barId: bar()->id, roleId: $roleId));

        return new Response(status: 201, headers: ['Location' => route('users.show', $user->id, false)]);
    }

    #[OAT\Put(path: '/members/{id}', tags: ['Members'], operationId: 'updateMember', description: 'Update a single member', summary: 'Update member', parameters: [
        new BAO\Parameters\BarIdHeaderParameter(),
        new BAO\Parameters\DatabaseIdParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(ref: BAO\Schemas\UpdateMemberRequest::class),
        ]
    ))]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    #[BAO\ValidationFailedResponse]
    public function update(int $id, MemberService $memberService, UpdateMemberRequest $request): Response
    {
        $barMembership = bar()
            ->memberships()
            ->where('bar_memberships.user_id', $id)
            ->firstOrFail();

        $authenticatedUser = $request->user();
        if ($authenticatedUser === null || $authenticatedUser->cannot('edit', $barMembership)) {
            abort(403);
        }

        $memberService->changeMemberRole(new ChangeMemberRoleRequest(
            memberId: $barMembership->id,
            roleId: $request->integer('role_id'),
        ));

        return new Response(status: 204);
    }

    #[OAT\Delete(path: '/members/{id}', tags: ['Members'], operationId: 'removeMember', description: 'Removes a specific user\'s membership from a bar', summary: 'Remove member', parameters: [
        new BAO\Parameters\BarIdHeaderParameter(),
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[BAO\NotFoundResponse]
    #[BAO\NotAuthorizedResponse]
    public function delete(MemberService $memberService, Request $request, int $id): Response
    {
        $barMembership = bar()
            ->memberships()
            ->where('bar_memberships.user_id', $id)
            ->firstOrFail();

        $authenticatedUser = $request->user();
        if ($authenticatedUser === null || $authenticatedUser->cannot('delete', $barMembership)) {
            abort(403);
        }

        $memberService->removeUserMembershipFromBar(new RemoveMemberRequest(
            $barMembership->user_id,
            bar()->id,
        ));

        return new Response(status: 204);
    }
}
