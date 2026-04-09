<?php

declare(strict_types=1);

namespace Tests\Infrastructure;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Bar\Member;
use BarAssistant\Domain\User\UserId;
use BarAssistant\Domain\Bar\MemberId;
use BarAssistant\Domain\Bar\MemberRepository;

/**
 * In-memory implementation of MemberRepository for testing purposes
 */
final class InMemoryMemberRepository implements MemberRepository
{
    /**
     * @param array<int, Member> $members
     */
    public function __construct(private array $members = [])
    {
    }

    public function deleteManyByUserId(UserId $userId): void
    {
        foreach ($this->members as $memberId => $member) {
            if ($member->getUserId()->equals($userId)) {
                unset($this->members[$memberId]);
            }
        }
    }

    public function delete(Member $member): void
    {
        unset($this->members[$member->getId()->value]);
    }

    public function save(Member $member): Member
    {
        if ($member->isTransient()) {
            // Assign a new ID for transient members
            $nextId = empty($this->members) ? 1 : max(array_keys($this->members)) + 1;
            $member->setId(new MemberId($nextId));
        }

        $memberId = $member->getId();
        if ($memberId === null) {
            throw new \DomainException('Member ID must be set');
        }

        $this->members[$memberId->value] = $member;

        return $member;
    }

    public function findById(MemberId $memberId): ?Member
    {
        return $this->members[$memberId->value] ?? null;
    }

    public function findUserInBar(UserId $userId, BarId $barId): ?Member
    {
        foreach ($this->members as $member) {
            if ($member->getUserId()->equals($userId) && $member->getBarId()->equals($barId)) {
                return $member;
            }
        }

        return null;
    }
}
