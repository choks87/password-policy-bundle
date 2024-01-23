<?php
declare(strict_types=1);

namespace Choks\PasswordPolicy\Contract;

use Choks\PasswordPolicy\Violation\ViolationList;

interface PolicyCheckerInterface
{
    public function validate(PasswordPolicySubjectInterface $subject): ViolationList;
}