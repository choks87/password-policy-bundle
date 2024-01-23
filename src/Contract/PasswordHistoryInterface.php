<?php
declare(strict_types=1);

namespace Choks\PasswordPolicy\Contract;

interface PasswordHistoryInterface
{
    public function add(PasswordPolicySubjectInterface $subject): void;

    public function remove(PasswordPolicySubjectInterface $subject): void;

    public function isUsed(PasswordPolicySubjectInterface $subject, HistoryPolicyInterface $historyPolicy,): bool;

    public function clear(): void;
}