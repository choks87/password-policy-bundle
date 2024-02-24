<?php
declare(strict_types=1);

namespace Choks\PasswordPolicy\Traits;

use Choks\PasswordPolicy\Exception\RuntimeException;

trait TimeFrameTrait
{
    private readonly ?string $unit;
    private readonly ?int    $period;

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function getPeriod(): ?int
    {
        return $this->period;
    }

    public function hasPeriod(): bool
    {
        return !empty($this->unit) && !empty($this->period);
    }

    public function getTimeInPast(): \DateTimeImmutable
    {
        if (!$this->hasPeriod()) {
            throw new RuntimeException('History Time period cannot be determined. Not configured.');
        }

        $interval = \DateInterval::createFromDateString(
            \sprintf("%d %s", $this->period, $this->unit)
        );

        if (false === $interval) {
            throw new RuntimeException('History Time period cannot be determined. Bad interval.');
        }

        return (new \DateTimeImmutable())->sub($interval);
    }
}