<?php

declare(strict_types=1);

namespace BarAssistant\Application\Matcher;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Cocktail\Glass;
use BarAssistant\Domain\Cocktail\GlassRepository;
use BarAssistant\Application\Matcher\DTO\GlassMatchRequest;

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

        if (isset($this->matchedGlasses[$matchName]) && !$this->matchedGlasses[$matchName]->isTransient()) {
            return $this->matchedGlasses[$matchName]->getId()->value;
        }

        $glasses = $this->glassRepository->findAllInBar(new BarId($request->barId));
        foreach ($glasses as $glass) {
            $this->matchedGlasses[$glass->getName()->toLowercase()] = $glass;
        }

        $existingGlass = $this->matchedGlasses[$matchName] ?? null;
        if ($existingGlass) {
            $this->matchedGlasses[$matchName] = $existingGlass;

            return $existingGlass->getId()?->value;
        }

        return null;
    }
}
