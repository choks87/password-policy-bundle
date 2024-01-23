<?php
declare(strict_types=1);

namespace Choks\PasswordPolicy\Violation;

use Choks\PasswordPolicy\Contract\ViolationInterface;

final class Violation implements ViolationInterface
{
    public function __construct(
        private readonly string $message,
    )
    {
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}