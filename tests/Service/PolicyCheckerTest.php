<?php
declare(strict_types=1);

namespace Choks\PasswordPolicy\Tests\Service;

use Choks\PasswordPolicy\Contract\PolicyCheckerInterface;
use Choks\PasswordPolicy\Tests\KernelTestCase;
use Choks\PasswordPolicy\Tests\Resources\App\Entity\Subject;

final class PolicyCheckerTest extends KernelTestCase
{
    private PolicyCheckerInterface $policyChecker;

    protected function setUp(): void
    {
        $this->policyChecker = self::getContainer()->get(PolicyCheckerInterface::class);
    }

    /**
     * @dataProvider providerForValidateTest
     */
    public function testValidate(?string $password, int $expectedViolationCount): void
    {
        $subject    = new Subject(1, $password);
        $violations = $this->policyChecker->validate($subject);

        self::assertCount($expectedViolationCount, $violations->getViolations());
    }

    public function providerForValidateTest(): iterable
    {
        yield 'Password is empty string' => ['', 5];
        yield 'Password is empty spaced string' => ['       ', 5];

        yield 'Does not have at least 8 characters' => ['Foo2!', 1];
        yield 'Does not have at least 1 uppercase character' => ['foobar200!', 1];
        yield 'Does not have at least 1 lowercase character' => ['FOOBAR200!', 1];
        yield 'Does not have at least 1 number' => ['FooBarBaz@!', 1];
        yield 'Does not have at least 1 special character' => ['FooBar200', 1];

        yield 'Having only good lowercase' => ['foo', 4];
        yield 'Having only good uppercase' => ['FOO', 4];
        yield 'Having only good specials' => ['!@#', 4];
        yield 'Having only good numbers' => ['123', 4];

        yield 'Satisfies minimum length but only with lowercase letters' => ['foofoofoo', 3];
        yield 'Satisfies minimum length but only with uppercase letters' => ['FOOFOOFOO', 3];
        yield 'Satisfies minimum length but only with special chars' => ['!@#%#@&^%^#@', 3];
        yield 'Satisfies minimum length but only with numbers' => ['1234567890', 3];

        yield 'Having only good lowercase and uppercase' => ['FooBar', 3];
        yield 'Having only good lowercase and specials' => ['foo!', 3];
        yield 'Having only good lowercase and numbers' => ['foo1', 3];

        yield 'Having only good uppercase and specials' => ['FOO!', 3];
        yield 'Having only good uppercase and numbers' => ['FOO1', 3];

        yield 'Valid Password with space as special character' => ['Qux200! ', 1];

        foreach (str_split("\"'!@#$%^&*()_+=-`~.,;:<>[]{}\\|") as $specialChar) {
            yield 'Valid special character '.$specialChar => ['FooBar1'.$specialChar, 0];
        }

        yield 'Valid Password' => ['FooBar2!', 0];
        yield 'Valid Password with Unicode letters' => ['ФооБар2!', 0];
    }
}