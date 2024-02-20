<?php

declare(strict_types=1);

namespace Choks\PasswordPolicy\Model;

use Choks\PasswordPolicy\Contract\HistoryPolicyInterface;
use Choks\PasswordPolicy\Exception\RuntimeException;

final class HistoryPolicy implements HistoryPolicyInterface
{
    public function __construct(
        private readonly ?int    $backTrackCount,
        private readonly ?string $backTrackTimeUnit,
        private readonly ?int    $backTrackTimeValue,
    ) {
    }

    public function getBackTrackTimeUnit(): ?string
    {
        return $this->backTrackTimeUnit;
    }

    public function getBackTrackTimeValue(): ?int
    {
        return $this->backTrackTimeValue;
    }

    public function isValid(): bool
    {
        return !empty($this->backTrackCount) || $this->hasPeriod();
    }

    public function getBackTrackCount(): ?int
    {
        return $this->backTrackCount;
    }

    public function hasPeriod(): bool
    {
        return !empty($this->backTrackTimeUnit) && !empty($this->backTrackTimeValue);
    }

    public function backTrackStartDateTime(): \DateTimeImmutable
    {
        if (!$this->hasPeriod()) {
            throw new RuntimeException('History Time period cannot be determined. Not configured.');
        }

        $interval = \DateInterval::createFromDateString(
            \sprintf("%d %s", $this->backTrackTimeValue, $this->backTrackTimeUnit)
        );

        if (false === $interval) {
            throw new RuntimeException('History Time period cannot be determined. Bad interval.');
        }

        return (new \DateTimeImmutable())->sub($interval);
    }
}