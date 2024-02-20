<?php
declare(strict_types=1);

namespace Choks\PasswordPolicy\Tests\Resources\App;

class PasswordPolicyWithDbalTestKernel extends PasswordPolicyTestKernel
{
    protected function getStorageConfig(): array
    {
        return [
            'dbal' => [
                'connection' => 'default',
                'table'      => 'password_history',
            ],
        ];
    }
}