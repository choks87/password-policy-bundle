<?php
declare(strict_types=1);

namespace Choks\PasswordPolicy\Tests\Adapter;

use Choks\PasswordPolicy\Adapter\CacheStorageAdapter;
use Choks\PasswordPolicy\Adapter\DbalStorageAdapter;
use Choks\PasswordPolicy\Contract\StorageAdapterInterface;
use Choks\PasswordPolicy\Tests\KernelWithDbalAdapterTestCase;
use Choks\PasswordPolicy\Tests\Resources\App\Entity\Subject;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

final class DbalAdapterTest extends KernelWithDbalAdapterTestCase
{
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
        $sql = 'SELECT * FROM password_history';
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
        self::assertEquals($expected, [...$this->storageAdapter->getPastPasswords($subject)]);
    }

    public function testGetPastNPasswords(): void
    {
        $subject = new Subject(1, 'bar');

        $this->storageAdapter->add($subject, 'baz');
        sleep(1);
        $this->storageAdapter->add($subject, 'waldoo');
        sleep(1);
        $this->storageAdapter->add($subject, 'fruit');

        $expected = ['fruit', 'waldoo'];
        self::assertEquals($expected, [...$this->storageAdapter->getPastPasswords($subject, 2)]);
    }

    public function testGetPastPasswordsWithStartingFrom(): void
    {
        $subject = new Subject(1, 'bar');

        $this->addMockedRecord($subject, 'baz', new \DateTimeImmutable('-2 hour'));
        $this->addMockedRecord($subject, 'waldoo', new \DateTimeImmutable('-1 hour'));
        $this->addMockedRecord($subject, 'fruit', new \DateTimeImmutable('-30 minutes'));

        $expected = ['fruit'];
        $data     = $this->storageAdapter->getPastPasswords($subject, 2, new \DateTimeImmutable('-40 minutes'));
        self::assertEquals($expected, [...$data]);
    }

    public function testClear(): void
    {
        $subject = new Subject(1, 'bar');

        $this->storageAdapter->add($subject, 'baz');
        $this->storageAdapter->add($subject, 'waldoo');
        $this->storageAdapter->add($subject, 'fruit');

        self::assertNotEmpty([...$this->storageAdapter->getPastPasswords($subject)]);

        $this->storageAdapter->clear();

        self::assertEmpty([...$this->storageAdapter->getPastPasswords($subject)]);
    }

    private function addMockedRecord(Subject $subject, string $password, \DateTimeImmutable $createdAt): void
    {
        $this->connection
            ->insert('password_history',
                     [
                         'subject_id'    => $subject->getIdentifier(),
                         'password'   => $password,
                         'created_at' => $createdAt,
                     ],
                     [
                         'created_at'   => Types::DATETIME_IMMUTABLE,
                     ],
            )
        ;
    }
}