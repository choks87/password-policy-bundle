<?php

declare(strict_types=1);

namespace Choks\PasswordPolicy;

use Choks\PasswordPolicy\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class PasswordPolicy extends Bundle
{
    public function getNamespace(): string
    {
        return 'password_policy';
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        return new Extension();
    }
}