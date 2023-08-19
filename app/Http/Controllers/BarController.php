<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Kami\Cocktail\Models\Bar;
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
        $bar->search_driver_api_key = $search->getActions()->getPublicApiKey();
        $bar->user_id = $request->user()->id;
        $bar->save();

        $bar->users()->save($request->user(), ['user_role_id' => UserRoleEnum::Admin->value]);

        $barService->openBar($bar);

        return new BarResource($bar);
    }
}
