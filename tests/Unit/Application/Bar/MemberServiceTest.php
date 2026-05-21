<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Bar;

use PHPUnit\Framework\TestCase;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Bar\Member;
use BarAssistant\Domain\User\UserId;
use BarAssistant\Domain\Bar\MemberId;
use BarAssistant\Domain\Bar\MemberRole;
use BarAssistant\Application\Bar\MemberService;
use Tests\Infrastructure\InMemoryMemberRepository;
use BarAssistant\Application\Bar\DTO\CreateMemberRequest;
use BarAssistant\Application\Bar\DTO\RemoveMemberRequest;
use BarAssistant\Application\Bar\DTO\ChangeMemberRoleRequest;
use BarAssistant\Application\Exception\EntityNotFoundException;

final class MemberServiceTest extends TestCase
{
    private InMemoryMemberRepository $memberRepository;
    private MemberService $service;

    protected function setUp(): void
    {
        $this->memberRepository = new InMemoryMemberRepository([
            1 => Member::create(
                userId: new UserId(10),
                barId: new BarId(1),
                role: MemberRole::Admin,
            )->setId(new MemberId(1)),
            2 => Member::create(
                userId: new UserId(11),
                barId: new BarId(1),
                role: MemberRole::General,
            )->setId(new MemberId(2)),
        ]);

        $this->service = new MemberService($this->memberRepository);
    }

    public function test_add_member_to_bar_creates_and_persists_member(): void
    {
        $member = $this->service->addMemberToBar(new CreateMemberRequest(
            userId: 20,
            barId: 1,
            roleId: 3,
        ));

        $this->assertNotNull($member->getId());
        $this->assertSame(20, $member->getUserId()->value);
        $this->assertSame(1, $member->getBarId()->value);
        $this->assertSame(MemberRole::General, $member->getRole());

        $persistedMember = $this->memberRepository->findUserInBar(new UserId(20), new BarId(1));
        $this->assertNotNull($persistedMember);
        $this->assertSame($member, $persistedMember);
    }

    public function test_add_member_to_bar_rejects_existing_membership(): void
    {
        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionMessage('User is already a member of this bar');

        $this->service->addMemberToBar(new CreateMemberRequest(
            userId: 10,
            barId: 1,
            roleId: 1,
        ));
    }

    public function test_remove_user_membership_from_bar_deletes_member(): void
    {
        $this->service->removeUserMembershipFromBar(new RemoveMemberRequest(
            userId: 10,
            barId: 1,
        ));

        $member = $this->memberRepository->findUserInBar(new UserId(10), new BarId(1));
        $this->assertNull($member);
    }

    public function test_remove_user_membership_from_bar_throws_when_missing(): void
    {
        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionMessage('Member not found');

        $this->service->removeUserMembershipFromBar(new RemoveMemberRequest(
            userId: 999,
            barId: 1,
        ));
    }

    public function test_change_member_role_updates_existing_member(): void
    {
        $this->service->changeMemberRole(new ChangeMemberRoleRequest(
            memberId: 2,
            roleId: 1,
        ));

        $member = $this->memberRepository->findById(new MemberId(2));
        $this->assertNotNull($member);
        $this->assertSame(MemberRole::Admin, $member->getRole());
    }

    public function test_change_member_role_throws_when_member_missing(): void
    {
        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionMessage('The member was not found');

        $this->service->changeMemberRole(new ChangeMemberRoleRequest(
            memberId: 999,
            roleId: 1,
        ));
    }
}
