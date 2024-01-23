<?php
declare(strict_types=1);

namespace Choks\PasswordPolicy\Exception;

use Choks\PasswordPolicy\Violation\ViolationList;

final class PolicyCheckException extends \Exception
{
    public function __construct(
        private readonly ViolationList $violations,
        string                         $message = "There are password policy violations.",
    ) {
        parent::__construct($message);
    }

    public function getViolations(): ViolationList
    {
        return $this->violations;
    }
}