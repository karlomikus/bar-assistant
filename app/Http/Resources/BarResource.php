<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use Kami\Cocktail\Search\SearchActionsAdapter;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Kami\Cocktail\Models\Bar
 */
class BarResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        $search = app(SearchActionsAdapter::class);

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'subtitle' => $this->subtitle,
            'description' => $this->description,
            'invite_code' => $this->invite_code,
            'status' => $this->getStatus()->value,
            'settings' => $this->settings ?? [],
            'search_driver_host' => $search->getActions()->getHost(),
            'search_driver_api_key' => $search->getActions()->getBarSearchApiKey($this->id),
            'created_at' => $this->created_at->toJson(),
            'updated_at' => $this->updated_at?->toJson() ?? null,
            'created_user' => new UserBasicResource($this->whenLoaded('createdUser')),
            'updated_user' => new UserBasicResource($this->whenLoaded('updatedUser')),
            'access' => [
                'role_id' => $this->memberships->where('user_id', $request->user()->id)->first()->user_role_id,
                'can_edit' => $request->user()->can('edit', $this->resource),
                'can_delete' => $request->user()->can('delete', $this->resource),
                'can_activate' => $request->user()->can('activate', $this->resource),
                'can_deactivate' => $request->user()->can('deactivate', $this->resource),
            ]
        ];
    }
}
