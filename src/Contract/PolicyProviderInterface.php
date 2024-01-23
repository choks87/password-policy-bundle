<?php
declare(strict_types=1);

namespace Choks\PasswordPolicy\Contract;

interface PolicyProviderInterface
{
    public function getPolicy(PasswordPolicySubjectInterface $subject): PolicyInterface;
}