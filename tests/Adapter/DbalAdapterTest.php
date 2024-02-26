<?php
declare(strict_types=1);

namespace Choks\PasswordPolicy\Tests\Adapter;

use Choks\PasswordPolicy\Adapter\DbalStorageAdapter;
use Choks\PasswordPolicy\Contract\StorageAdapterInterface;
use Choks\PasswordPolicy\Criteria\SearchCriteria;
use Choks\PasswordPolicy\Enum\Order;
use Choks\PasswordPolicy\Tests\AdapterTestTrait;
use Choks\PasswordPolicy\Tests\KernelWithDbalAdapterTestCase;
use Choks\PasswordPolicy\Tests\Resources\App\Entity\Subject;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

final class DbalAdapterTest extends KernelWithDbalAdapterTestCase
{
    use AdapterTestTrait;

    private StorageAdapterInterface $storageAdapter;

    private Connection $connection;

    protected function setUp(): void
    {
        $this->connection     = self::getContainer()->get('doctrine.dbal.default_connection');
        $this->storageAdapter = new DbalStorageAdapter($this->connection, 'password_history');
    }

    public function testAdd(): void
    {
        $sql = 'SELECT * FROM password_history';
        self::assertEquals(0, $this->connection->executeQuery($sql)->rowCount());

        $this->storageAdapter->add(new Subject(1, 'bar'), 'baz');

        self::assertEquals(1, $this->connection->executeQuery($sql)->rowCount());
    }

    public function testRemove(): void
    {
        $sql     = 'SELECT * FROM password_history';
        $subject = new Subject(1, 'bar');

        $this->storageAdapter->add($subject, 'baz');
        self::assertEquals(1, $this->connection->executeQuery($sql)->rowCount());
        $this->storageAdapter->remove($subject);
        self::assertEquals(0, $this->connection->executeQuery($sql)->rowCount());

    }

    public function testGetPastPasswords(): void
    {
        $subject = new Subject(1, 'bar');


        $this->storageAdapter->add($subject, 'baz');
        sleep(1);
        $this->storageAdapter->add($subject, 'waldoo');
        sleep(1);
        $this->storageAdapter->add($subject, 'fruit');

        $expected = ['fruit', 'waldoo', 'baz'];
        $criteria = (new SearchCriteria())->setSubject($subject)->setOrder(Order::DESC);
        self::assertEquals($expected, $this->getPasswords($criteria));
    }

    public function testGetPastNPasswords(): void
    {
        $subject = new Subject(1, 'bar');

        $this->storageAdapter->add($subject, 'baz');
        sleep(1);
        $this->storageAdapter->add($subject, 'waldoo');
        sleep(1);
        $this->storageAdapter->add($subject, 'fruit');

        $expected = ['baz', 'waldoo'];
        $criteria = (new SearchCriteria())->setSubject($subject)->setLimit(2);
        self::assertEquals($expected, $this->getPasswords($criteria));
    }

    public function testGetPastPasswordsWithStartingFrom(): void
    {
        $subject = new Subject(1, 'bar');

        $this->addMockedRecord($subject, 'baz', new \DateTimeImmutable('-2 hour'));
        $this->addMockedRecord($subject, 'waldoo', new \DateTimeImmutable('-1 hour'));
        $this->addMockedRecord($subject, 'fruit', new \DateTimeImmutable('-30 minutes'));

        $expected = ['fruit'];

        $criteria = (new SearchCriteria())
            ->setSubject($subject)
            ->setLimit(2)
            ->setStartDate(new \DateTimeImmutable('-40 minutes'));
        self::assertEquals($expected, $this->getPasswords($criteria));
    }

    public function testClear(): void
    {
        $subject = new Subject(1, 'bar');

        $this->storageAdapter->add($subject, 'baz');
        $this->storageAdapter->add($subject, 'waldoo');
        $this->storageAdapter->add($subject, 'fruit');

        $criteria = (new SearchCriteria())->setSubject($subject);

        self::assertNotEmpty($this->getPasswords($criteria));

        $this->storageAdapter->clear();

        self::assertEmpty($this->getPasswords($criteria));
    }

    private function addMockedRecord(Subject $subject, string $password, \DateTimeImmutable $createdAt): void
    {
        $this->connection
            ->insert('password_history',
                     [
                         'subject_id' => $subject->getIdentifier(),
                         'password'   => $password,
                         'created_at' => $createdAt,
                     ],
                     [
                         'created_at' => Types::DATETIME_IMMUTABLE,
                     ],
            )
        ;
    }
}