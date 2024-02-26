<?php
declare(strict_types=1);

namespace Choks\PasswordPolicy\Contract;

interface TimeFrameInterface
{
    public function getUnit(): ?string;

    public function getPeriod(): ?int;

    public function hasPeriod(): bool;

    public function getTimeInPast(): \DateTimeImmutable;
}