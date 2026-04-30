<?php

declare(strict_types=1);

namespace BarAssistant\Application\Bar;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Bar\Member;
use BarAssistant\Domain\User\UserId;
use BarAssistant\Domain\Bar\MemberId;
use BarAssistant\Domain\Bar\MemberRole;
use BarAssistant\Domain\Bar\MemberRepository;
use BarAssistant\Application\Bar\DTO\CreateMemberRequest;
use BarAssistant\Application\Bar\DTO\RemoveMemberRequest;
use BarAssistant\Application\Bar\DTO\ChangeMemberRoleRequest;
use BarAssistant\Application\Exception\EntityNotFoundException;

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
            throw new EntityNotFoundException('User is already a member of this bar');
        }

        $role = MemberRole::fromInt($request->roleId);
        $member = Member::create($userId, $barId, $role);

        $this->memberRepository->save($member);

        return $member;
    }

    public function removeUserMembershipFromBar(RemoveMemberRequest $request): void
    {
        $userId = new UserId($request->userId);
        $barId = new BarId($request->barId);

        $member = $this->memberRepository->findUserInBar($userId, $barId);
        if ($member === null) {
            throw new EntityNotFoundException('Member not found');
        }

        $this->memberRepository->delete($member);
    }

    public function changeMemberRole(ChangeMemberRoleRequest $request): void
    {
        $member = $this->memberRepository->findById(new MemberId($request->memberId));
        if ($member === null || $member->isTransient()) {
            throw new EntityNotFoundException('The member was not found');
        }

        $newRole = MemberRole::fromInt($request->roleId);
        $member->changeRole($newRole);

        $this->memberRepository->save($member);
    }
}
