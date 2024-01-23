<?php
declare(strict_types=1);

namespace Choks\PasswordPolicy\Contract;

interface HistoryPolicyInterface
{
    public function getBackTrackTimeUnit(): ?string;

    public function getBackTrackTimeValue(): ?int;

    public function isValid(): bool;

    public function hasPeriod(): bool;

    public function getBackTrackCount(): ?int;

    public function backTrackStartDateTime(): \DateTimeImmutable;
}