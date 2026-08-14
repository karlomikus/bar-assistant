<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Bar;

use BarAssistant\Domain\User\UserId;

interface MemberRepository
{
    public function save(Member $member): Member;

    public function delete(Member $member): void;

    public function findById(MemberId $memberId): ?Member;

    public function findUserInBar(UserId $userId, BarId $barId): ?Member;

    public function deleteManyByUserId(UserId $userId): void;
}
