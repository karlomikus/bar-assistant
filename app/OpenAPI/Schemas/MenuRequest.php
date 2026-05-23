<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use Illuminate\Http\Request;
use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['is_enabled', 'categories'])]
class MenuRequest
{
    /**
     * @param array<MenuCategoryRequest> $categories
     */
    public function __construct(
        #[OAT\Property(property: 'is_enabled')]
        public bool $isEnabled = false,
        #[OAT\Property(items: new OAT\Items(type: MenuCategoryRequest::class))]
        public array $categories = [],
    ) {
    }

    public static function fromIlluminateRequest(Request $request): self
    {
        /** @var array<mixed> */
        $formItems = $request->post('categories', []);

        $categories = [];
        foreach ($formItems as $formCategory) {
            $categories[] = MenuCategoryRequest::fromArray($formCategory);
        }

        return new self(
            isEnabled: $request->boolean('is_enabled', false),
            categories: $categories,
        );
    }
}
