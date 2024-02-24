<?php
declare(strict_types=1);

namespace Choks\PasswordPolicy\Adapter;

use Choks\PasswordPolicy\Contract\PasswordPolicySubjectInterface;
use Choks\PasswordPolicy\Contract\StorageAdapterInterface;
use Choks\PasswordPolicy\Criteria\SearchCriteria;
use Choks\PasswordPolicy\Enum\Order;
use Choks\PasswordPolicy\Exception\NotImplementedException;
use Choks\PasswordPolicy\Exception\RuntimeException;
use Choks\PasswordPolicy\Exception\StorageException;
use Choks\PasswordPolicy\ValueObject\PasswordRecord;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\AdapterInterface;

/**
 * @psalm-type Item = array{
 *     password: string,
 *     created_at: \DateTimeImmutable
 * }
 */
final class CacheStorageAdapter implements StorageAdapterInterface
{
    public function __construct(private readonly AdapterInterface $cache, private readonly string $keyPrefix)
    {
    }

    public function add(PasswordPolicySubjectInterface $subject, string $hashedPassword): void
    {
        $cacheItem = $this->cache->getItem($this->getCacheKey($subject));

        /** @var array<Item>|array{} $value */
        $value = $cacheItem->get() ?? [];

        $value[] = [
            'password'   => $hashedPassword,
            'created_at' => new \DateTimeImmutable(),
        ];

        $cacheItem->set($value);
        $outcome = $this->cache->save($cacheItem);

        if (false === $outcome) {
            throw new StorageException('Unable to store password into history.');
        }
    }

    public function remove(PasswordPolicySubjectInterface $subject): void
    {
        try {
            $this->cache->deleteItem($this->getCacheKey($subject));
        } catch (InvalidArgumentException $e) {
            throw new StorageException(
                  \sprintf("Unable to remove passwords for subject %s from history.", $subject->getIdentifier())
                , 0,
                  $e
            );
        }
    }

    /**
     * {@inheritDoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function get(SearchCriteria $criteria): iterable
    {
        if (null === $criteria->getSubject()) {
            throw new NotImplementedException(
                'Getting all records from cache, without specifying subject is not yet implemented.'
            );
        }

        try {
            $cacheItem = $this->cache->getItem($this->getCacheKey($criteria->getSubject()));
        } catch (InvalidArgumentException $e) {
            throw new StorageException(
                \sprintf("Unable to fetch password history records for criteria %s", $criteria),
                0,
                $e
            );
        }
        /** @var array<Item>|array{} $value */
        $value = $cacheItem->get() ?? [];

        if (Order::DESC === $criteria->getOrder()) {
            $value = \array_reverse($value);
        }

        $recordCount = 0;
        foreach ($value as $item) {
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
                $criteria->getSubject()->getIdentifier(),
                $item['password'],
                $item['created_at'],
            );

            $recordCount++;
        }
    }

    public function clear(): void
    {
        $this->cache->clear();
    }

    private function getCacheKey(PasswordPolicySubjectInterface $subject): string
    {
        return \sprintf("%s_%s", $this->keyPrefix, $subject->getIdentifier());
    }
}