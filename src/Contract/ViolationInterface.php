<?php
declare(strict_types=1);

namespace Choks\PasswordPolicy\Contract;

interface ViolationInterface
{
    public function getMessage(): string;
}