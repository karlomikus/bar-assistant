<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Kami\Cocktail\Models\Bar;
use Kami\Cocktail\Models\User;
use OpenApi\Attributes as OAT;
use Kami\Cocktail\Jobs\SetupBar;
use Kami\Cocktail\OpenAPI as BAO;
use Illuminate\Support\Facades\Cache;
use Kami\Cocktail\Jobs\SyncBarRecipes;
use Kami\Cocktail\Http\Requests\BarRequest;
use BarAssistant\Application\Bar\BarService;
use Kami\Cocktail\Jobs\StartBarOptimization;
use Kami\Cocktail\Models\Enums\UserRoleEnum;
use Kami\Cocktail\Http\Resources\BarResource;
use Kami\Cocktail\Models\Enums\BarStatusEnum;
use BarAssistant\Application\Bar\MemberService;
use Illuminate\Http\Resources\Json\JsonResource;
use BarAssistant\Application\Bar\DTO\CreateBarRequest;
use BarAssistant\Application\Bar\DTO\UpdateBarRequest;
use BarAssistant\Application\Bar\DTO\CreateMemberRequest;
use Kami\Cocktail\OpenAPI\Schemas\BarRequest as SchemasBarRequest;

class BarController extends Controller
{
    #[OAT\Get(path: '/bars', tags: ['Bars'], summary: 'List bars', operationId: 'listBars', description: 'Show a list of bars current authenticated user has access to. Includes bars he is a member of.')]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapItemsWithData(BarResource::class),
    ])]
    public function index(Request $request): JsonResource
    {
        $bars = Bar::select('bars.*')
            ->join('bar_memberships', 'bar_memberships.bar_id', '=', 'bars.id')
            ->where('bar_memberships.user_id', $request->user()->id)
            ->with('createdUser', 'memberships', 'images')
            ->get();

        return BarResource::collection($bars);
    }

    #[OAT\Get(path: '/bars/{id}', tags: ['Bars'], operationId: 'showBar', description: 'Show information about a specific bar.', summary: 'Show bar', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapObjectWithData(BarResource::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function show(Request $request, int $id): JsonResource
    {
        $bar = Cache::remember('ba:bar:' . $id, 60 * 60 * 24, function () use ($id) {
            $bar = Bar::findOrFail($id);

            if (!$bar->slug) {
                $bar->generateSlug();
                $bar->save();
            }

            return $bar;
        });

        if ($request->user()->cannot('show', $bar)) {
            abort(403);
        }

        $bar->load('createdUser', 'updatedUser', 'images');

        return new BarResource($bar);
    }

    #[OAT\Post(path: '/bars', tags: ['Bars'], operationId: 'saveBar', description: 'Create a new bar with optional default data included. Assigns current authenticated user as a member with admin role.', summary: 'Create bar', requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(ref: BAO\Schemas\BarRequest::class),
        ]
    ))]
    #[OAT\Response(response: 201, description: 'Successful response', headers: [
        new OAT\Header(header: 'Location', description: 'URL of the new resource', schema: new OAT\Schema(type: 'string')),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\ValidationFailedResponse]
    public function store(BarService $barService, MemberService $memberService, BarRequest $request): Response
    {
        if ($request->user()->cannot('create', Bar::class)) {
            abort(403, 'You can not create any more bars.');
        }

        Cache::forget('metrics_bass_total_bars');

        $request->validate([
            'slug' => 'nullable|unique:bars,slug',
        ]);

        $barRequest = SchemasBarRequest::fromLaravelRequest($request);

        $barCreateResult = $barService->createBar(new CreateBarRequest(
            name: $barRequest->name,
            createdUserId: $request->user()->id,
            subtitle: $barRequest->subtitle,
            description: $barRequest->description,
            isPublic: $barRequest->isPublic,
            isInviteCodeEnabled: $barRequest->invitesEnabled,
            images: $barRequest->images,
            defaultCurrency: $barRequest->defaultCurrency,
            defaultUnits: $barRequest->defaultUnits,
        ));

        $memberService->addMemberToBar(new CreateMemberRequest(
            userId: $request->user()->id,
            barId: $barCreateResult->id,
            roleId: 1,
        ));

        SetupBar::dispatch($barCreateResult->id, $request->user()->id, $barRequest->options);

        return new Response(status: 201)->header('Location', route('bars.show', $barCreateResult->id, false));
    }

    #[OAT\Put(path: '/bars/{id}', tags: ['Bars'], operationId: 'updateBar', description: 'Update a specific bar.', summary: 'Update bar', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(ref: BAO\Schemas\BarRequest::class),
        ]
    ))]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapObjectWithData(BarResource::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    #[BAO\ValidationFailedResponse]
    public function update(int $id, BarService $barService, BarRequest $request): Response
    {
        $bar = Bar::findOrFail($id);

        if ($request->user()->cannot('edit', $bar)) {
            abort(403);
        }

        $request->validate([
            'slug' => 'nullable|unique:bars,slug,' . $bar->id,
        ]);

        Cache::forget('ba:bar:' . $bar->id);

        $barRequest = SchemasBarRequest::fromLaravelRequest($request);

        $barService->updateBar(new UpdateBarRequest(
            barId: $bar->id,
            name: $barRequest->name,
            description: $barRequest->description,
            userId: $request->user()->id,
            subtitle: $barRequest->subtitle,
            isInviteCodeEnabled: $barRequest->invitesEnabled,
            isPublic: $barRequest->isPublic,
            images: $barRequest->images,
            defaultCurrency: $barRequest->defaultCurrency,
            defaultUnits: $barRequest->defaultUnits,
        ));

        return new Response(status: 204);
    }

    #[OAT\Delete(path: '/bars/{id}', tags: ['Bars'], operationId: 'deleteBar', description: 'Delete a specific bar.', summary: 'Delete bar', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function delete(Request $request, BarService $barService, int $id): Response
    {
        $bar = Bar::findOrFail($id);

        if ($request->user()->cannot('delete', $bar)) {
            abort(403);
        }

        Cache::forget('metrics_bass_total_bars');
        Cache::forget('ba:bar:' . $bar->id);

        $barService->deleteBar($bar->id);

        return new Response(null, 204);
    }

    #[OAT\Post(path: '/bars/join', tags: ['Bars'], operationId: 'joinBar', description: 'Join a bar as a guest role via invite code. Target bar must be active and have invite code enabled.', summary: 'Join a bar', requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(type: 'object', properties: [
                new OAT\Property(type: 'string', property: 'invite_code', example: '01H8S3VH2HTEB3D893AW8NTBBC'),
            ]),
        ]
    ))]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapObjectWithData(BarResource::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function join(MemberService $memberService, Request $request): Response
    {
        $barToJoin = Bar::where('invite_code', $request->post('invite_code'))->firstOrFail();

        if ($barToJoin->getStatus() === BarStatusEnum::Deactivated) {
            abort(403);
        }

        $memberService->addMemberToBar(new CreateMemberRequest(
            userId: $request->user()->id,
            barId: $barToJoin->id,
            roleId: 4,
        ));

        return new Response(status: 204);
    }

    #[OAT\Post(path: '/bars/{id}/transfer', tags: ['Bars'], operationId: 'transferBarOwnership', description: 'Transfer a bar to another user. Gives another use complete access of a bar. Only bar owners can start bar transfer.', summary: 'Transfer ownership', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(type: 'object', properties: [
                new OAT\Property(type: 'integer', property: 'user_id', example: 1, description: 'Database id of a user you want to transfer ownership to'),
            ]),
        ]
    ))]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function transfer(Request $request, int $id): Response
    {
        $bar = Bar::findOrFail($id);

        if ($request->user()->cannot('transfer', $bar)) {
            abort(403);
        }

        $newOwner = User::findOrFail((int) $request->post('user_id'));

        $bar->created_user_id = $newOwner->id;
        $bar->save();

        $barOwnership = $newOwner->joinBarAs($bar, UserRoleEnum::Admin);
        $barOwnership->user_role_id = UserRoleEnum::Admin->value; // Needed for existing members
        $barOwnership->save();

        return response()->noContent();
    }

    #[OAT\Post(path: '/bars/{id}/status', tags: ['Bars'], operationId: 'toggleBarStatus', description: 'Update current status of a bar', summary: 'Update status', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(type: 'object', properties: [
                new OAT\Property(ref: BarStatusEnum::class, property: 'status', example: BarStatusEnum::Active->value),
            ]),
        ]
    ))]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function toggleBarStatus(Request $request, int $id): Response
    {
        $bar = Bar::findOrFail($id);

        if ($request->user()->cannot('edit', $bar)) {
            abort(403);
        }

        foreach ($request->user()->ownedBars ??  [] as $bar) {
            Cache::forget('ba:bar:' . $bar->id);
        }

        Cache::forget('metrics_bass_total_bars');

        $newStatus = $request->post('status');

        // Unsubscribed users can only have 1 active bar
        $hasMaxActiveBars = !$request->user()->hasActiveSubscription()
            && $request->user()->ownedBars()->where('status', BarStatusEnum::Active->value)->count() >= 1;

        if ($newStatus === BarStatusEnum::Active->value && $request->user()->can('activate', $bar) && !$hasMaxActiveBars) {
            $bar->status = $newStatus;
            $bar->save();

            return response()->noContent();
        }

        if ($newStatus === BarStatusEnum::Deactivated->value && $request->user()->can('deactivate', $bar)) {
            $bar->status = $newStatus;
            $bar->save();

            return response()->noContent();
        }

        abort(403);
    }

    #[OAT\Post(path: '/bars/{id}/optimize', tags: ['Bars'], operationId: 'optimizeBar', description: 'Triggers bar optimizations. Updates all cocktail ABVs, rebuilds ingredient hierarchy, updates search index. Limited call to once per minute.', summary: 'Optimize bar', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[BAO\NotAuthorizedResponse]
    #[BAO\RateLimitResponse]
    #[BAO\NotFoundResponse]
    public function optimize(Request $request, int $id): Response
    {
        $bar = Bar::findOrFail($id);

        if ($request->user()->cannot('edit', $bar)) {
            abort(403);
        }

        StartBarOptimization::dispatch($bar->id);

        return response()->noContent();
    }

    #[OAT\Post(path: '/bars/{id}/sync-datapack', tags: ['Bars'], operationId: 'syncBarDatapack', description: 'Triggers synchronization of recipes from the default datapack. Matches data by name, does not overwrite your existing recipes or ingredients.', summary: 'Sync recipes', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[BAO\NotAuthorizedResponse]
    #[BAO\RateLimitResponse]
    #[BAO\NotFoundResponse]
    public function syncDatapack(Request $request, int $id): Response
    {
        $bar = Bar::findOrFail($id);

        if ($request->user()->cannot('edit', $bar)) {
            abort(403);
        }

        SyncBarRecipes::dispatch($bar, $request->user());

        return response()->noContent();
    }
}
