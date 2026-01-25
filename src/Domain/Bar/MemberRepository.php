<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Bar;

interface MemberRepository
{
    public function save(Member $member): Member;

    public function findById(MemberId $memberId): ?Member;
}
