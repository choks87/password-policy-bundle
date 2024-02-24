<?php
declare(strict_types=1);

namespace Choks\PasswordPolicy\Adapter;

use Choks\PasswordPolicy\Contract\PasswordPolicySubjectInterface;
use Choks\PasswordPolicy\Contract\StorageAdapterInterface;
use Choks\PasswordPolicy\Criteria\SearchCriteria;
use Choks\PasswordPolicy\Exception\StorageException;
use Choks\PasswordPolicy\ValueObject\PasswordRecord;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;

final class DbalStorageAdapter implements StorageAdapterInterface
{
    public function __construct(
        private readonly Connection $connection,
        private readonly string     $tableName,
    ) {
    }

    public function add(PasswordPolicySubjectInterface $subject, string $hashedPassword): void
    {
        try {

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
        } catch (\Exception $e) {
            throw new StorageException('Unable to store password into history.', 0, $e);
        }
    }

    public function remove(PasswordPolicySubjectInterface $subject): void
    {
        try {
            $this->connection
                ->delete(
                    $this->tableName,
                    [
                        'subject_id' => $subject->getIdentifier(),
                    ],
                )
            ;
        } catch (\Exception $e) {
            throw new StorageException(
                \sprintf("Unable to remove passwords for subject %s from history.",$subject->getIdentifier())
                ,0,
                $e
            );
        }
    }

    public function clear(): void
    {
        try {
            $this->connection
                ->createQueryBuilder()
                ->delete($this->tableName)
                ->executeQuery()
            ;
        } catch (\Exception $e) {
            throw new StorageException('Unable to clear password history storage.', 0, $e);
        }
    }

    /**
     * {@inheritDoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function get(SearchCriteria $criteria): iterable
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder
            ->select('h.*')
            ->from($this->tableName, 'h')
        ;

        if (null !== $criteria->getOrder()) {
            $queryBuilder->orderBy('h.created_at', $criteria->getOrder()->value);
        }

        if (null !== $criteria->getSubject()) {
            $queryBuilder->andWhere($queryBuilder->expr()->eq('h.subject_id', ':subject_id'));
            $queryBuilder->setParameter('subject_id', $criteria->getSubject()->getIdentifier());
        }

        if (null !== $criteria->getStartDate()) {
            $queryBuilder->andWhere($queryBuilder->expr()->gte('h.created_at', ':start_date'));
            $queryBuilder->setParameter('start_date', $criteria->getStartDate(), Types::DATETIME_IMMUTABLE);
        }

        if (null !== $criteria->getEndDate()) {
            $queryBuilder->andWhere($queryBuilder->expr()->lte('h.created_at', ':end_date'));
            $queryBuilder->setParameter('end_date', $criteria->getEndDate(), Types::DATETIME_IMMUTABLE);
        }

        if (null !== $criteria->getLimit()) {
            $queryBuilder->setMaxResults($criteria->getLimit());
        }

        try {
            /** @var array{
             *     subject_id: int|non-empty-string,
             *     password: non-empty-string,
             *     created_at: non-empty-string,
             * } $item
             */
            foreach ($queryBuilder->executeQuery()->iterateAssociative() as $item) {
                yield new PasswordRecord(
                    (string)$item['subject_id'],
                    $item['password'],
                    new \DateTimeImmutable($item['created_at']),
                );
            }
        } catch (Exception $e) {
            throw new StorageException(
                \sprintf("Unable to fetch password history records for criteria %s", $criteria),
                0,
                $e
            );
        }
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