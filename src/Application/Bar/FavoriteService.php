<?php

declare(strict_types=1);

namespace BarAssistant\Application\Bar;

use BarAssistant\Domain\Bar\MemberId;
use BarAssistant\Domain\Cocktail\CocktailId;
use BarAssistant\Domain\Bar\MemberRepository;
use BarAssistant\Application\Bar\DTO\FavoriteResult;
use BarAssistant\Application\Bar\DTO\FavoriteRequest;
use BarAssistant\Application\Exception\EntityNotFoundException;

final readonly class FavoriteService
{
    public function __construct(
        private MemberRepository $memberRepository,
    ) {
    }

    public function toggleFavorite(FavoriteRequest $request): FavoriteResult
    {
        $member = $this->memberRepository->findById(new MemberId($request->memberId));

        if ($member === null) {
            throw new EntityNotFoundException('Member not found.');
        }

        $cocktailId = new CocktailId($request->cocktailId);

        if ($member->isCocktailFavorited($cocktailId)) {
            $member->removeCocktailFromFavorites($cocktailId);
            $this->memberRepository->save($member);

            return new FavoriteResult(
                cocktailId: $request->cocktailId,
                isFavorited: false,
            );
        }

        $member->addCocktailToFavorites($cocktailId);
        $this->memberRepository->save($member);

        return new FavoriteResult(
            cocktailId: $request->cocktailId,
            isFavorited: true,
        );
    }
}
