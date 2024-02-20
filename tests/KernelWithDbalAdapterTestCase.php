<?php
declare(strict_types=1);

namespace Choks\PasswordPolicy\Tests;

use Choks\PasswordPolicy\Tests\Resources\App\PasswordPolicyWithDbalTestKernel;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class KernelWithDbalAdapterTestCase extends WebTestCase
{
    protected static function getKernelClass(): string
    {
        return PasswordPolicyWithDbalTestKernel::class;
    }
}