<?php
declare(strict_types=1);

namespace Choks\PasswordPolicy\Tests;

use Choks\PasswordPolicy\Tests\Resources\App\PasswordPolicyTestKernel;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class KernelTestCase extends WebTestCase
{
    protected static function getKernelClass(): string
    {
        return PasswordPolicyTestKernel::class;
    }

}