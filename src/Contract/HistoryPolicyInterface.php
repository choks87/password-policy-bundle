<?php
declare(strict_types=1);

namespace Choks\PasswordPolicy\Contract;

interface HistoryPolicyInterface extends TimeFrameInterface
{

    public function isValid(): bool;

    public function getLast(): ?int;
}