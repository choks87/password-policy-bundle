<?php

declare(strict_types=1);

namespace Choks\PasswordPolicy\Model;

use Choks\PasswordPolicy\Contract\ExpirationPolicyInterface;
use Choks\PasswordPolicy\Traits\TimeFrameTrait;

final class ExpirationPolicy implements ExpirationPolicyInterface
{
    use TimeFrameTrait;

    public function __construct(?string $unit, ?int $period)
    {
        $this->unit   = $unit;
        $this->period = $period;
    }
}
