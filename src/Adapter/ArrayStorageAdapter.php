<?php
declare(strict_types=1);

namespace Choks\PasswordPolicy\Adapter;

use Choks\PasswordPolicy\Contract\PasswordPolicySubjectInterface;
use Choks\PasswordPolicy\Contract\StorageAdapterInterface;
use Choks\PasswordPolicy\Criteria\SearchCriteria;
use Choks\PasswordPolicy\Enum\Order;
use Choks\PasswordPolicy\ValueObject\PasswordRecord;

/**
 * @psalm-type Item = array{
 *     subject_id: string|int,
 *     password: string,
 *     created_at: \DateTimeImmutable
 * }
 */
final class ArrayStorageAdapter implements StorageAdapterInterface
{
    /**
     * @var Item[]
     */
    private array $list = [];

    public function add(PasswordPolicySubjectInterface $subject, string $hashedPassword): void
    {
        $this->list[] = [
            'subject_id' => $subject->getIdentifier(),
            'password'   => $hashedPassword,
            'created_at' => new \DateTimeImmutable(),
        ];
    }

    public function remove(PasswordPolicySubjectInterface $subject): void
    {
        foreach ($this->list as $key => $item) {
            if ($item['subject_id'] === $subject->getIdentifier()) {
                unset($this->list[$key]);
            }
        }

        $this->list = \array_values($this->list);
    }

    public function clear(): void
    {
        $this->list = [];
    }

    /**
     * {@inheritDoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function get(SearchCriteria $criteria): iterable
    {
        $list = $this->list;

        if (null !== $criteria->getSubject()) {
            $list = \array_filter($list, static function (array $item) use ($criteria) {
                return (string)$item['subject_id'] === $criteria->getSubject()->getIdentifier();
            });
        }

        if (Order::DESC === $criteria->getOrder()) {
            $list = \array_reverse($list);
        }

        $recordCount = 0;
        foreach ($list as $item) {
            if (null !== $criteria->getStartDate() && $item['created_at'] < $criteria->getStartDate()) {
                continue;
            }

            if (null !== $criteria->getEndDate() && $item['created_at'] > $criteria->getEndDate()) {
                continue;
            }

            if (null !== $criteria->getLimit() && $recordCount >= $criteria->getLimit()) {
                break;
            }

            yield new PasswordRecord(
                (string)$item['subject_id'],
                $item['password'],
                $item['created_at'],
            );

            $recordCount++;
        }
    }

    /**
     * @return Item[]
     */
    public function &getListByReference(): array
    {
        return $this->list;
    }
}