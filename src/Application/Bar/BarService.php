<?php

declare(strict_types=1);

namespace BarAssistant\Application\Bar;

use BarAssistant\Domain\Bar\Bar;
use BarAssistant\Domain\Bar\BarRepository;
use BarAssistant\Domain\Common\Authors;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\User\UserId;

final readonly class MemberService
{
    public function __construct(
        private BarRepository $barRepository,
    ) {
    }

    public function createBar(): Bar
    {
        $bar = Bar::create(
            name: Name::fromString('asfasfas'),
            authors: Authors::createdBy(new UserId(1)),
            recordTimestamps: RecordTimestamps::createdNow(),
            ingredientInventory: [],
        );

        $this->barRepository->save($bar);

        return $bar;
    }
}
