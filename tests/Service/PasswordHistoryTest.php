<?php
declare(strict_types=1);

namespace Choks\PasswordPolicy\Tests\Service;

use Choks\PasswordPolicy\Adapter\ArrayStorageAdapter;
use Choks\PasswordPolicy\Contract\HistoryPolicyInterface;
use Choks\PasswordPolicy\Contract\StorageAdapterInterface;
use Choks\PasswordPolicy\Model\HistoryPolicy;
use Choks\PasswordPolicy\Service\PasswordHistory;
use Choks\PasswordPolicy\Tests\KernelTestCase;
use Choks\PasswordPolicy\Tests\Resources\App\Entity\Subject;

/**
 * @group time-sensitive
 */
final class PasswordHistoryTest extends KernelTestCase
{
    private PasswordHistory         $passwordHistory;
    private StorageAdapterInterface $storageAdapter;

    protected function setUp(): void
    {
        $this->storageAdapter  = self::getContainer()->get(StorageAdapterInterface::class);
        $this->passwordHistory = self::getContainer()->get(PasswordHistory::class);
        $this->passwordHistory->clear();
    }

    protected function tearDown(): void
    {
        $this->passwordHistory->clear();
    }

    /**
     * @dataProvider providerForValidateTest
     */
    public function testAddThenAskIfUsed(
        HistoryPolicyInterface $policy,
        array                  $passwordsInHistory,
        string                 $currentPassword,
        bool                   $expectedUsed,
    ): void {
        $subject = new Subject(1);

        foreach ($passwordsInHistory as $password) {
            $this->passwordHistory->add($subject->setPlainPassword($password));
        }

        $this->decreaseDateTimeInStorage(5000);

        $isUsed = $this->passwordHistory->isUsed($subject->setPlainPassword($currentPassword), $policy);

        self::assertEquals($expectedUsed, $isUsed);
    }

    public function providerForValidateTest(): iterable
    {
        yield 'Password is within past 3 passwords, with no period limit' => [
            new HistoryPolicy(3, null, null),
            ['foo', 'bar', 'baz'],
            'foo',
            true,
        ];

        yield 'Password is within past 4 passwords, but NOT within last 3 as policy asks' => [
            new HistoryPolicy(3, null, null),
            ['foo', 'bar', 'baz', 'fruit'],
            'foo',
            false,
        ];

        yield 'Password is NOT within past 3 passwords' => [
            new HistoryPolicy(3, null, null),
            ['foo', 'bar', 'baz'],
            'fruit',
            false,
        ];

        yield 'Password is within past 3 passwords, but only 2 written' => [
            new HistoryPolicy(3, null, null),
            ['foo', 'bar', 'fruit'],
            'foo',
            true,
        ];

        yield 'Password is within past 9 passwords, policy says min past is 10' => [
            new HistoryPolicy(10, null, null),
            ['foo', 'bar', 'fruit', 'waldoo', 'quinx', 'qux', 'doom', 'colge', 'fred', 'gizmo'],
            'foo',
            true,
        ];

        yield 'Password is within past 9 passwords, policy says min past 5 10' => [
            new HistoryPolicy(5, null, null),
            ['foo', 'bar', 'fruit', 'waldoo', 'quinx', 'qux', 'doom', 'colge', 'fred', 'gizmo'],
            'foo',
            false,
        ];
    }

    /**
     * @dataProvider providerForAddTestWithTimeFrame
     */
    public function testAddThenAskIfUsedWithinTimeFrame(
        HistoryPolicyInterface $policy,
        int                    $interval,
        bool                   $expectedUsed,
    ): void {
        $password = 'foo';
        $subject  = new Subject(1, $password);

        $this->passwordHistory->add($subject);

        $this->decreaseDateTimeInStorage($interval);

        $isUsed = $this->passwordHistory->isUsed($subject, $policy);

        self::assertEquals($expectedUsed, $isUsed);
    }

    public function providerForAddTestWithTimeFrame(): iterable
    {
        yield 'Password is within past passwords, but it is older than policy requests' => [
            new HistoryPolicy(3, 'day', 1),
            2 * 3600 * 24,
            false,
        ];

        yield 'Password is within past passwords, and younger than policy requests' => [
            new HistoryPolicy(3, 'day', 1),
            3600,
            true,
        ];
    }

    public function testIsUsedWhenInvalidHistoryPolicy(): void
    {
        $subject       = new Subject(1, 'foo');
        $historyPolicy = $this->createMock(HistoryPolicyInterface::class);
        $historyPolicy->method('isValid')->willReturn(false);

        $result = $this->passwordHistory->isUsed($subject, $historyPolicy);

        self::assertFalse($result);
    }

    protected function decreaseDateTimeInStorage(int $intervalSec): void
    {
        if (!$this->storageAdapter instanceof ArrayStorageAdapter) {
            throw new \Exception('For test, only use ArrayStorage Adapter.');
        }

        $list = &$this->storageAdapter->getListByReference();

        foreach ($list as $index => &$item) {
            $item['created_at'] = $item['created_at']
                ->setTimestamp(
                    $item['created_at']->getTimestamp() - ($intervalSec * ($index + 1))
                )
            ;
        }
    }
}