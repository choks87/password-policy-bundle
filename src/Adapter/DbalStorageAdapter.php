<?php
declare(strict_types=1);

namespace Choks\PasswordPolicy\Adapter;

use Choks\PasswordPolicy\Contract\PasswordPolicySubjectInterface;
use Choks\PasswordPolicy\Contract\StorageAdapterInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;

final class DbalStorageAdapter implements StorageAdapterInterface
{
    public function __construct(
        private readonly string     $tableName,
        private readonly Connection $connection,
    ) {
    }

    public function add(PasswordPolicySubjectInterface $subject, string $hashedPassword): void
    {
        $this
            ->connection
            ->insert($this->tableName,
                     [
                         'subject_id' => $subject->getIdentifier(),
                         'password'   => $hashedPassword,
                         'created_at' => new \DateTimeImmutable(),
                     ],
                     [
                         'created_at' => Types::DATETIME_IMMUTABLE,
                     ]
            )
        ;
    }

    public function remove(PasswordPolicySubjectInterface $subject): void
    {
        $this->connection
            ->delete(
                $this->tableName,
                [
                    'subject_id' => $subject->getIdentifier(),
                ],
            )
        ;
    }

    public function clear(): void
    {
        $this->connection
            ->createQueryBuilder()
            ->delete($this->tableName)
            ->executeQuery()
        ;
    }

    /**
     * @return iterable<string>
     */
    public function getPastPasswords(
        PasswordPolicySubjectInterface $subject,
        ?int                           $lastN,
        ?\DateTimeImmutable            $startingFrom,
    ): iterable {
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder
            ->select('h.password')
            ->from($this->tableName, 'h')
            ->orderBy('h.created_at', 'asc')
            ->where('h.subject_id = :subject_id')
            ->setParameter('subject_id', $subject->getIdentifier())
        ;

        if ($startingFrom) {
            $queryBuilder->andWhere($queryBuilder->expr()->gte('h.created_at', ':start_from'));
            $queryBuilder->setParameter('start_from', $startingFrom, Types::DATETIME_IMMUTABLE);
        }

        if ($lastN) {
            $queryBuilder->setMaxResults($lastN);
        }

        return $queryBuilder->executeQuery()->iterateColumn(); // @phpstan-ignore-line
    }

    public function postGenerateSchema(GenerateSchemaEventArgs $eventArgs): void
    {
        $schema = $eventArgs->getSchema();
        $table  = $schema->createTable($this->tableName);

        $table->addColumn('subject_id', Types::STRING, ['length' => 64]);
        $table->addColumn('password', Types::STRING, ['length' => 128]);
        $table->addColumn('created_at', Types::DATETIME_IMMUTABLE);

        $table->addIndex(['subject_id']);
        $table->addIndex(['created_at']);
    }
}