<?php

namespace Choks\PasswordPolicy\Event;

use Choks\PasswordPolicy\ValueObject\Password;

class ExpiredPasswordEvent
{
    public function __construct(
        private readonly string $subjectId,
        private readonly \DateTimeImmutable $expiredAt,
        private readonly \DateTimeImmutable $createdAt,
    )
    {
    }

    public function getSubjectIdentifier(): string
    {
        return $this->subjectId;
    }

    public function getExpiredAt(): \DateTimeImmutable
    {
        return $this->expiredAt;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}