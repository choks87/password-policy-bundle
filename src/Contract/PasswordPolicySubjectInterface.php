<?php

declare(strict_types=1);

namespace Choks\PasswordPolicy\Contract;

interface PasswordPolicySubjectInterface
{
    public function getIdentifier(): string;

    public function getPlainPassword(): ?string;
}