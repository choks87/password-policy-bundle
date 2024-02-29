<?php
declare(strict_types=1);

namespace Choks\PasswordPolicy\ValueObject;

final class Password
{
    private readonly string|int         $subjectId;
    private readonly string             $hashedPassword;
    private readonly \DateTimeImmutable $createdAt;

    public function __construct(
        string|int          $subjectId,
        string              $hashedPassword,
        ?\DateTimeImmutable $createdAt = null,
    ) {
        $this->subjectId      = $subjectId;
        $this->hashedPassword = $hashedPassword;
        $this->createdAt      = $createdAt ?? new \DateTimeImmutable('now');
    }

    public function getSubjectIdentifier(): string
    {
        return (string)$this->subjectId;
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