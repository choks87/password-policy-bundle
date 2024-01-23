<?php
declare(strict_types=1);

namespace Choks\PasswordPolicy\Contract;

interface StorageAdapterInterface
{
    public function add(PasswordPolicySubjectInterface $subject, string $hashedPassword): void;

    public function remove(PasswordPolicySubjectInterface $subject): void;

    /**
     * @return iterable<string>
     */
    public function getPastPasswords(PasswordPolicySubjectInterface $subject, ?int $lastN, ?\DateTimeImmutable $startingFrom): iterable;

    public function clear(): void;
}