<?php
declare(strict_types=1);

namespace Choks\PasswordPolicy\ValueObject;

use Choks\PasswordPolicy\Contract\PasswordPolicySubjectInterface;

final class PasswordRecord
{
    public function __construct(
        private readonly string             $subjectId,
        private readonly string             $hashedPassword,
        private readonly \DateTimeImmutable $createdAt,
    ) {
    }

    public function getSubjectIdentifier(): string
    {
        return $this->subjectId;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getHashedPassword(): string
    {
        return $this->hashedPassword;
    }
}