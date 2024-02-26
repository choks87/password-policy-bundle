<?php

declare(strict_types=1);

namespace Choks\PasswordPolicy\Model;

use Choks\PasswordPolicy\Contract\HistoryPolicyInterface;
use Choks\PasswordPolicy\Traits\TimeFrameTrait;

final class HistoryPolicy implements HistoryPolicyInterface
{
    use TimeFrameTrait;

    private readonly ?int $last;

    public function __construct(?int $last, ?string $unit, ?int $period)
    {
        $this->last   = $last;
        $this->unit   = $unit;
        $this->period = $period;
    }

    public function isValid(): bool
    {
        return !empty($this->last) || $this->hasPeriod();
    }

    public function getLast(): ?int
    {
        return $this->last;
    }
}