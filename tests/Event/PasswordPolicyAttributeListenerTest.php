<?php
declare(strict_types=1);

namespace Choks\PasswordPolicy\Tests\Event;

use Choks\PasswordPolicy\Exception\PolicyCheckException;
use Choks\PasswordPolicy\Tests\KernelTestCase;
use Choks\PasswordPolicy\Tests\KernelWithDbalAdapterTestCase;
use Choks\PasswordPolicy\Tests\Resources\App\Entity\ListenedSubject;
use Choks\PasswordPolicy\Tests\Resources\App\Entity\Subject;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

final class PasswordPolicyAttributeListenerTest extends KernelWithDbalAdapterTestCase
{

    private EntityManagerInterface $entityManager;

    private Connection $connection;
    private string     $tableName;

    protected function setUp(): void
    {
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->connection    = self::getContainer()->get('password_policy.storage.dbal.connection');
        $this->tableName     = self::getContainer()->getParameter('password_policy.storage.dbal.table');
    }

    public function testThrowsExceptionWhenSubjectWithBadPasswordGetSaved(): void
    {
        $subject = new ListenedSubject(1, 'foo');

        $this->expectException(PolicyCheckException::class);

        $this->entityManager->persist($subject);
        $this->entityManager->flush($subject);

        self::assertNotNull($this->entityManager->refresh($subject));
    }

    public function testSuccessfulSaveInHistory(): void
    {
        $subject1 = new ListenedSubject(1, 'FooBarBaz1@');
        $this->entityManager->persist($subject1);
        $this->entityManager->flush($subject1);

        $subject2 = new ListenedSubject(2, 'FooBarBaz1@');
        $this->entityManager->persist($subject2);
        $this->entityManager->flush($subject2);

        $count = $this->connection->executeQuery('SELECT * FROM '.$this->tableName)->rowCount();
        self::assertEquals(2, $count);

        $this->entityManager->remove($subject2);
        $this->entityManager->flush();

        $count = $this->connection->executeQuery('SELECT * FROM '.$this->tableName)->rowCount();
        self::assertEquals(1, $count);
    }

    public function testSuccessfulSaveButNotInHistoryOrValidated(): void
    {
        $subject1 = new Subject(1, 'Foo');
        $this->entityManager->persist($subject1);
        $this->entityManager->flush($subject1);

        $count = $this->connection->executeQuery('SELECT * FROM '.$this->tableName)->rowCount();
        self::assertEquals(0, $count);
    }
}