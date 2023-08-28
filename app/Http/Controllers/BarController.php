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
        return BarResource::collection(
            $request->user()->ownedBars
        );
    }

    public function show(Request $request, int $id): JsonResource
    {
        $bar = Bar::findOrFail($id);

        if (!$request->user()->isBarOwner($bar)) {
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

        $bar = new Bar();
        $bar->name = $request->post('name');
        $bar->subtitle = $request->post('subtitle');
        $bar->description = $request->post('description');
        $bar->created_user_id = $request->user()->id;
        $bar->invite_code = (string) new Ulid();
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

        if (!$request->user()->isBarOwner($bar)) {
            abort(403);
        }

        Cache::forget('ba:bar:' . $bar->id);

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
}
