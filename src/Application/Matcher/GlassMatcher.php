<?php

declare(strict_types=1);

namespace BarAssistant\Application\Matcher;

use BarAssistant\Application\Matcher\DTO\GlassMatchRequest;
use BarAssistant\Domain\Cocktail\Glass;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Cocktail\GlassRepository;

final class GlassMatcher
{
    /** @var array<string, Glass> */
    private array $matchedGlasses = [];

    public function __construct(
        private GlassRepository $glassRepository,
    ) {
    }

    public function matchByName(GlassMatchRequest $request): ?int
    {
        $matchName = mb_strtolower($request->glassName);

        if (isset($this->matchedGlasses[$matchName])) {
            return $this->matchedGlasses[$matchName]->getId()->value;
        }

        $this->matchedGlasses = $this->glassRepository->findAllInBar(new BarId($request->barId));

        $existingGlass = $this->matchedGlasses[$matchName] ?? null;
        if ($existingGlass) {
            $this->matchedGlasses[$matchName] = $existingGlass;

            return $existingGlass->getId()->value;
        }

        return null;
    }
}
