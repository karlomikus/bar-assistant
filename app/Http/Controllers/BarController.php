<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Kami\Cocktail\Models\Bar;
use Kami\Cocktail\Models\User;
use OpenApi\Attributes as OAT;
use Kami\Cocktail\Models\Image;
use Kami\Cocktail\Jobs\SetupBar;
use Illuminate\Http\JsonResponse;
use Kami\Cocktail\OpenAPI as BAO;
use Illuminate\Support\Facades\Cache;
use Kami\Cocktail\Http\Requests\BarRequest;
use Kami\Cocktail\Jobs\StartBarOptimization;
use Kami\Cocktail\Models\Enums\UserRoleEnum;
use Kami\Cocktail\Http\Resources\BarResource;
use Kami\Cocktail\Models\Enums\BarStatusEnum;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Resources\BarMembershipResource;
use Kami\Cocktail\Jobs\SyncBarRecipes;
use Kami\Cocktail\OpenAPI\Schemas\BarRequest as SchemasBarRequest;

class BarController extends Controller
{
    #[OAT\Get(path: '/bars', tags: ['Bars'], summary: 'List bars', operationId: 'listBars', description: 'Show a list of bars user has access to. Includes bars that user has made and bars he is a member of.')]
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

    #[OAT\Get(path: '/bars/{id}', tags: ['Bars'], operationId: 'showBar', description: 'Show information about a specific bar', summary: 'Show bar', parameters: [
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

    #[OAT\Post(path: '/bars', tags: ['Bars'], operationId: 'saveBar', description: 'Create a new bar', summary: 'Create bar', requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(ref: BAO\Schemas\BarRequest::class),
        ]
    ))]
    #[OAT\Response(response: 201, description: 'Successful response', content: [
        new BAO\WrapObjectWithData(BarResource::class),
    ], headers: [
        new OAT\Header(header: 'Location', description: 'URL of the new resource', schema: new OAT\Schema(type: 'string')),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\ValidationFailedResponse]
    public function store(BarRequest $request): JsonResponse
    {
        if ($request->user()->cannot('create', Bar::class)) {
            abort(403, 'You can not create anymore bars');
        }

        Cache::forget('metrics_bass_total_bars');

        $request->validate([
            'slug' => 'nullable|unique:bars,slug',
        ]);

        $barRequest = SchemasBarRequest::fromLaravelRequest($request);

        $bar = $barRequest->toLaravelModel();
        $bar->created_user_id = $request->user()->id;
        $bar->save();

        if (count($barRequest->images) > 0) {
            try {
                $imageModels = Image::findOrFail($barRequest->images);
                $bar->attachImages($imageModels);
            } catch (\Throwable $e) {
                abort(500, $e->getMessage());
            }
        }

        $bar->load('createdUser', 'updatedUser', 'images');

        $request->user()->joinBarAs($bar, UserRoleEnum::Admin);

        SetupBar::dispatch($bar, $request->user(), $barRequest->options);

        return (new BarResource($bar))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('bars.show', $bar->id));
    }

    #[OAT\Put(path: '/bars/{id}', tags: ['Bars'], operationId: 'updateBar', description: 'Update a specific bar', summary: 'Update bar', parameters: [
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
    public function update(int $id, BarRequest $request): JsonResource
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
        $bar = $barRequest->toLaravelModel($bar);

        if ($request->filled('slug')) {
            $bar->slug = $barRequest->slug;
        }

        $bar->status = BarStatusEnum::Active->value;
        $bar->updated_user_id = $request->user()->id;
        $bar->updated_at = now();
        $bar->save();

        if (count($barRequest->images) > 0) {
            try {
                $imageModels = Image::findOrFail($barRequest->images);
                $bar->attachImages($imageModels);
            } catch (\Throwable $e) {
                abort(500, $e->getMessage());
            }
        }

        $bar->load('createdUser', 'updatedUser', 'images');

        return new BarResource($bar);
    }

    #[OAT\Delete(path: '/bars/{id}', tags: ['Bars'], operationId: 'deleteBar', description: 'Delete a specific bar', summary: 'Delete bar', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function delete(Request $request, int $id): Response
    {
        $bar = Bar::findOrFail($id);

        if ($request->user()->cannot('delete', $bar)) {
            abort(403);
        }

        Cache::forget('metrics_bass_total_bars');
        Cache::forget('ba:bar:' . $bar->id);

        $bar->delete();

        return new Response(null, 204);
    }

    #[OAT\Post(path: '/bars/join', tags: ['Bars'], operationId: 'joinBar', description: 'Join a bar via invite code', summary: 'Join a bar', requestBody: new OAT\RequestBody(
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
    public function join(Request $request): JsonResource
    {
        $barToJoin = Bar::where('invite_code', $request->post('invite_code'))->firstOrFail();

        if ($barToJoin->status === BarStatusEnum::Deactivated->value) {
            abort(403);
        }

        $request->user()->joinBarAs($barToJoin, UserRoleEnum::Guest);

        return new BarResource($barToJoin);
    }

    #[OAT\Delete(path: '/bars/{id}/memberships', tags: ['Bars'], operationId: 'leaveBar', description: 'Deletes a user\'s membership to a bar', summary: 'Leave a bar', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[BAO\NotFoundResponse]
    public function leave(Request $request, int $id): Response
    {
        $bar = Bar::findOrFail($id);

        $request->user()->leaveBar($bar);

        return new Response(null, 204);
    }

    #[OAT\Delete(path: '/bars/{id}/memberships/{userId}', tags: ['Bars'], operationId: 'removeBarMembership', description: 'Removes a specific user\'s membership from a bar', summary: 'Remove member', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
        new OAT\Parameter(name: 'userId', in: 'path', required: true, description: 'Database id of a user', schema: new OAT\Schema(type: 'integer')),
    ])]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[BAO\NotFoundResponse]
    #[BAO\NotAuthorizedResponse]
    public function removeMembership(Request $request, int $id, int $userId): Response
    {
        $bar = Bar::findOrFail($id);

        if ($request->user()->cannot('deleteMembership', $bar)) {
            abort(403);
        }

        if ((int) $request->user()->id === (int) $userId) {
            abort(400, 'You cannot remove your own bar membership.');
        }

        $bar->memberships()->where('user_id', $userId)->delete();

        return new Response(null, 204);
    }

    #[OAT\Get(path: '/bars/{id}/memberships', tags: ['Bars'], operationId: 'listBarMembership', description: 'List all bar members', summary: 'List members', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapObjectWithData(BarMembershipResource::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function memberships(Request $request, int $id): JsonResource
    {
        $bar = Bar::findOrFail($id);

        if ($request->user()->cannot('show', $bar)) {
            abort(403);
        }

        $bar->load('memberships');

        return BarMembershipResource::collection($bar->memberships);
    }

    #[OAT\Post(path: '/bars/{id}/transfer', tags: ['Bars'], operationId: 'transferBarOwnership', description: 'Transfer a bar to another user.', summary: 'Transfer ownership', parameters: [
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
    public function transfer(Request $request, int $id): JsonResponse
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

        return response()->json(status: 204);
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
    public function toggleBarStatus(Request $request, int $id): JsonResponse
    {
        $bar = Bar::findOrFail($id);

        if ($request->user()->cannot('edit', $bar)) {
            abort(403);
        }

        Cache::forget('metrics_bass_total_bars');

        $newStatus = $request->post('status');

        // Unsubscribed users can only have 1 active bar
        $hasMaxActiveBars = !$request->user()->hasActiveSubscription()
            && $request->user()->ownedBars()->where('status', BarStatusEnum::Active->value)->count() >= 1;

        if ($newStatus === BarStatusEnum::Active->value && $request->user()->can('activate', $bar) && !$hasMaxActiveBars) {
            $bar->status = $newStatus;
            $bar->save();

            return response()->json(status: 204);
        }

        if ($newStatus === BarStatusEnum::Deactivated->value && $request->user()->can('deactivate', $bar)) {
            $bar->status = $newStatus;
            $bar->save();

            return response()->json(status: 204);
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
    public function optimize(Request $request, int $id): JsonResponse
    {
        $bar = Bar::findOrFail($id);

        if ($request->user()->cannot('edit', $bar)) {
            abort(403);
        }

        StartBarOptimization::dispatch($bar->id);

        return response()->json(status: 204);
    }

    #[OAT\Post(path: '/bars/{id}/sync-datapack', tags: ['Bars'], operationId: 'syncBarDatapack', description: 'Triggers synchronization of recipes from the default datapack. Matches data by name, does not overwrite your existing recipes or ingredients.', summary: 'Sync recipes', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[BAO\NotAuthorizedResponse]
    #[BAO\RateLimitResponse]
    #[BAO\NotFoundResponse]
    public function syncDatapack(Request $request, int $id): JsonResponse
    {
        $bar = Bar::findOrFail($id);

        if ($request->user()->cannot('edit', $bar)) {
            abort(403);
        }

        SyncBarRecipes::dispatch($bar, $request->user());

        return response()->json(status: 204);
    }
}
