<?php
declare(strict_types=1);

namespace Choks\PasswordPolicy\Tests\Service;

use Choks\PasswordPolicy\Contract\HistoryPolicyInterface;
use Choks\PasswordPolicy\Service\PasswordHistory;
use Choks\PasswordPolicy\Tests\KernelTestCase;
use Choks\PasswordPolicy\Tests\Resources\App\Entity\Subject;
use Choks\PasswordPolicy\Model\HistoryPolicy;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

/**
 * @group time-sensitive
 */
final class PasswordHistoryTest extends KernelTestCase
{
    private Connection      $connection;
    private string          $tableName;
    private PasswordHistory $passwordHistory;

    protected function setUp(): void
    {
        $this->passwordHistory = self::getContainer()->get(PasswordHistory::class);
        $this->connection      = self::getContainer()->get('password_policy.storage.dbal.connection');
        $this->tableName       = self::getContainer()->getParameter('password_policy.storage.dbal.table');
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

        $this->decreaseDateTimeInDB(5000);

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

        $this->decreaseDateTimeInDB($interval);

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

    private function decreaseDateTimeInDB(int $intervalSec): void
    {
        $rows = $this->connection
            ->createQueryBuilder()
            ->select('h.*')
            ->from($this->tableName, 'h')
            ->executeQuery()
            ->iterateAssociative()
        ;

        foreach ($rows as $index => $row) {
            $createdAt    = new \DateTimeImmutable($row['created_at']);
            $newCreatedAt = $createdAt->setTimestamp($createdAt->getTimestamp() - ($intervalSec * ($index + 1)));

            $this->connection
                ->update($this->tableName,
                         [
                             'created_at' => $newCreatedAt->format(DATE_ATOM),
                         ],
                         [
                             'subject_id' => $row['subject_id'],
                             'password'   => $row['password'],
                         ],
                )
            ;
        }
    }
}