<?php

declare(strict_types=1);

namespace BarAssistant\Application\Bar;

use BarAssistant\Application\Bar\DTO\CreateMemberRequest;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Bar\Member;
use BarAssistant\Domain\Bar\MemberRepository;
use BarAssistant\Domain\Bar\MemberRole;
use BarAssistant\Domain\User\UserId;
use DomainException;

final readonly class MemberService
{
    public function __construct(
        private MemberRepository $memberRepository,
    ) {
    }

    public function addMemberToBar(CreateMemberRequest $request): Member
    {
        $userId = new UserId($request->userId);
        $barId = new BarId($request->barId);

        $existingMember = $this->memberRepository->findUserInBar($userId, $barId);
        if ($existingMember !== null) {
            throw new DomainException('User is already a member of this bar');
        }

        $role = MemberRole::fromString($request->role);

        $member = Member::create($userId, $barId, $role);

        $this->memberRepository->save($member);

        return $member;
    }

    public function changeMemberRole()
    {
        
    }
}
