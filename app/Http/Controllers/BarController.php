<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Kami\Cocktail\Models\Bar;
use Symfony\Component\Uid\Ulid;
use Illuminate\Http\JsonResponse;
use Kami\Cocktail\Services\SetupBar;
use Illuminate\Support\Facades\Cache;
use Kami\Cocktail\Models\UserRoleEnum;
use Kami\Cocktail\Http\Requests\BarRequest;
use Kami\Cocktail\Http\Resources\BarResource;
use Kami\Cocktail\Search\SearchActionsAdapter;
use Illuminate\Http\Resources\Json\JsonResource;

class BarController extends Controller
{
    public function index(Request $request): JsonResource
    {
        $bars = Bar::select('bars.*')
            ->join('bar_memberships', 'bar_memberships.bar_id', '=', 'bars.id')
            ->where('bar_memberships.user_id', $request->user()->id)
            ->with('createdUser')
            ->get();

        return BarResource::collection($bars);
    }

    public function show(Request $request, int $id): JsonResource
    {
        $bar = Bar::findOrFail($id);

        if ($request->user()->cannot('show', $bar)) {
            abort(403);
        }

        $bar->load('createdUser', 'updatedUser');

        return new BarResource($bar);
    }

    public function store(SearchActionsAdapter $search, SetupBar $setupBarService, BarRequest $request): JsonResponse
    {
        if ($request->user()->cannot('create', Bar::class)) {
            abort(403, 'You can not create anymore bars');
        }

        $inviteEnabled = (bool) $request->post('enable_invites', '1');

        $bar = new Bar();
        $bar->name = $request->post('name');
        $bar->subtitle = $request->post('subtitle');
        $bar->description = $request->post('description');
        $bar->created_user_id = $request->user()->id;
        $bar->invite_code = $inviteEnabled ? (string) new Ulid() : null;
        $bar->save();

        $bar->search_driver_api_key = $search->getActions()->getBarSearchApiKey($bar->id);
        $bar->save();

        $bar->users()->save($request->user(), ['user_role_id' => UserRoleEnum::Admin->value]);

        $setupBarService->openBar($bar, $request->user());

        return (new BarResource($bar))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('bars.show', $bar->id));
    }

    public function update(int $id, BarRequest $request): JsonResource
    {
        $bar = Bar::findOrFail($id);

        if ($request->user()->cannot('edit', $bar)) {
            abort(403);
        }

        Cache::forget('ba:bar:' . $bar->id);

        $inviteEnabled = (bool) $request->post('enable_invites', '1');
        if ($inviteEnabled && $bar->invite_code === null) {
            $bar->invite_code = (string) new Ulid();
        } else {
            $bar->invite_code = null;
        }

        $bar->name = $request->post('name');
        $bar->description = $request->post('description');
        $bar->subtitle = $request->post('subtitle');
        $bar->updated_user_id = $request->user()->id;
        $bar->updated_at = now();
        $bar->save();

        return new BarResource($bar);
    }

    public function delete(Request $request, int $id): Response
    {
        $bar = Bar::findOrFail($id);

        if ($request->user()->cannot('delete', $bar)) {
            abort(403);
        }

        Cache::forget('ba:bar:' . $bar->id);

        $bar->delete();

        return response(null, 204);
    }

    public function join(Request $request): JsonResource
    {
        $barToJoin = Bar::where('invite_code', $request->post('invite_code'))->firstOrFail();

        $barToJoin->users()->save($request->user(), ['user_role_id' => UserRoleEnum::General->value]);

        return new BarResource($barToJoin);
    }

    public function leave(Request $request, int $id): Response
    {
        $bar = Bar::findOrFail($id);

        $request->user()->getBarMembership($bar->id)->delete();

        return response(status: 204);
    }
}
