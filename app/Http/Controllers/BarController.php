<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Kami\Cocktail\Models\Bar;
use Symfony\Component\Uid\Ulid;
use Kami\Cocktail\Models\UserRoleEnum;
use Kami\Cocktail\Services\BarService;
use Kami\Cocktail\Http\Resources\BarResource;
use Kami\Cocktail\Search\SearchActionsAdapter;
use Illuminate\Http\Resources\Json\JsonResource;

class BarController extends Controller
{
    public function index(Request $request): JsonResource
    {
        return BarResource::collection($request->user()->ownedBars);
    }

    public function store(SearchActionsAdapter $search, BarService $barService, Request $request): JsonResource
    {
        if ($request->user()->cannot('create', Bar::class)) {
            abort(403, 'You can not create anymore bars');
        }

        $bar = new Bar();
        $bar->name = $request->post('name');
        $bar->subtitle = $request->post('subtitle');
        $bar->description = $request->post('description');
        $bar->user_id = $request->user()->id;
        $bar->invite_code = new Ulid();
        $bar->save();

        $bar->search_driver_api_key = $search->getActions()->getBarSearchApiKey($bar->id);
        $bar->save();

        $bar->users()->save($request->user(), ['user_role_id' => UserRoleEnum::Admin->value]);

        $barService->openBar($bar, $request->user());

        return new BarResource($bar);
    }

    public function delete(Request $request, int $id): Response
    {
        $bar = Bar::findOrFail($id);

        if ($request->user()->cannot('delete', $bar)) {
            abort(403);
        }

        $bar->delete();

        return response(null, 204);
    }
}
