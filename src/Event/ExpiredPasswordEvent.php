<?php
declare(strict_types=1);

namespace Choks\PasswordPolicy\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event is fired when upon checking, is determined that Subject (ex. User)
 * has expired password as last password used
 */
final class ExpiredPasswordEvent extends Event
{
    public const NAME = 'EXPIRED_PASSWORD';

    public function __construct(
        private readonly string             $subjectId,
        private readonly \DateTimeImmutable $expiredAt,
        private readonly \DateTimeImmutable $createdAt,
    ) {
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