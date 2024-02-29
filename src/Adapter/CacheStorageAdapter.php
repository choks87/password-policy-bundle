<?php
declare(strict_types=1);

namespace Choks\PasswordPolicy\Adapter;

use Choks\PasswordPolicy\Contract\PasswordPolicySubjectInterface;
use Choks\PasswordPolicy\Contract\StorageAdapterInterface;
use Choks\PasswordPolicy\Criteria\SearchCriteria;
use Choks\PasswordPolicy\Enum\Order;
use Choks\PasswordPolicy\Exception\StorageException;
use Choks\PasswordPolicy\ValueObject\Password;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\AdapterInterface;

/**
 * @psalm-type Item = array{
 *     password: string,
 *     created_at: \DateTimeImmutable
 * }
 *
 * @psalm-type SubjectList = iterable<non-empty-string>
 */
final class CacheStorageAdapter implements StorageAdapterInterface
{
    private const SUBJECT_LIST_KEY = 'subject_list';

    public function __construct(private readonly AdapterInterface $cache, private readonly string $keyPrefix)
    {
    }

    public function add(Password $password): void
    {
        $passwords   = $this->getSubjectPasswords($password->getSubjectIdentifier());
        $passwords[] = $password;
        $this->saveSubjectPasswords($password->getSubjectIdentifier(), $passwords);

        $this->addToSubjectList($password->getSubjectIdentifier());

    }

    public function removeForSubject(PasswordPolicySubjectInterface $subject): void
    {
        try {
            $this->cache->deleteItem($this->getSubjectCacheKey($subject));
            $this->removeFromSubjectList($subject->getIdentifier());
        } catch (InvalidArgumentException $e) {
            throw new StorageException('Unable to remove passwords for subject.', 0, $e);
        }
    }

    public function clear(): void
    {
        try {
            foreach ($this->getSubjectList() as $subjectPasswordsCacheKey) {
                $this->cache->deleteItem($this->getSubjectCacheKey($subjectPasswordsCacheKey));
            }
            $this->cache->deleteItem($this->getSubjectListKey());
        } catch (InvalidArgumentException $e) {
            throw new StorageException('Unable to clear all passwords from cache storage.', 0, $e);
        }
    }

    /**
     * {@inheritDoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function get(SearchCriteria $criteria): iterable
    {
        if (null !== $criteria->getSubject()) {
            $records = $this->getSubjectPasswords($criteria->getSubject());
        }

        if (null === $criteria->getSubject()) {
            $records = [];
            foreach ($this->getSubjectList() as $subjectIdentifier) {
                $records = [...$records, ...$this->getSubjectPasswords($subjectIdentifier)];
            }
        }

        if (Order::DESC === $criteria->getOrder()) {
            $records = \array_reverse($records);
        }

        $recordCount = 0;
        /** @var Password $item */
        foreach ($records as $item) {
            if (null !== $criteria->getStartDate() && $item->getCreatedAt() < $criteria->getStartDate()) {
                continue;
            }

            if (null !== $criteria->getLimit() && $recordCount >= $criteria->getLimit()) {
                break;
            }

            yield $item;

            $recordCount++;
        }
    }

    private function getSubjectCacheKey(PasswordPolicySubjectInterface|string $subject): string
    {
        if ($subject instanceof PasswordPolicySubjectInterface) {
            $subject = $subject->getIdentifier();
        }

        return \sprintf("%s_%s", $this->keyPrefix, $subject);
    }

    /**
     * @return array<Password>
     */
    private function getSubjectPasswords(PasswordPolicySubjectInterface|string $subject): array
    {
        try {
            $cacheItem = $this->cache->getItem($this->getSubjectCacheKey($subject));
        } catch (InvalidArgumentException $e) {
            throw new StorageException('Unable to fetch passwords from cache storage.', 0, $e);
        }

        $value = $cacheItem->get();

        if (empty($value) || !is_array($value)) {
            $value = [];
        }

        return $value;
    }

    /**
     * @param  iterable<Password>  $passwords
     */
    private function saveSubjectPasswords(string $subjectId, iterable $passwords): void
    {
        try {
            $cacheItem = $this->cache->getItem($this->getSubjectCacheKey($subjectId));
        } catch (InvalidArgumentException $e) {
            throw new StorageException('Unable to store password into history.', 0, $e);
        }

        $cacheItem->set($passwords);

        $outcome = $this->cache->save($cacheItem);

        if (false === $outcome) {
            throw new StorageException('Unable to store password into history.');
        }
    }

    private function getSubjectListKey(): string
    {
        return \sprintf("%s_%s", $this->keyPrefix, self::SUBJECT_LIST_KEY);
    }

    /**
     * @return array<string>
     */
    private function getSubjectList(): array
    {
        try {
            $cacheItem = $this->cache->getItem($this->getSubjectListKey());
        } catch (InvalidArgumentException $e) {
            throw new StorageException('Unable to fetch subject list from cache storage.', 0, $e);
        }

        $value = $cacheItem->get();

        if (empty($value) || !is_array($value)) {
            $value = [];
        }

        return $value;
    }

    /**
     * @param  iterable<string>  $list
     */
    private function saveSubjectList(iterable $list): void
    {
        try {
            $cacheItem = $this->cache->getItem($this->getSubjectListKey());
        } catch (InvalidArgumentException $e) {
            throw new StorageException('Unable to save subject list to cache storage.', 0, $e);
        }

        $cacheItem->set($list);
        $this->cache->save($cacheItem);
    }

    private function addToSubjectList(string $subjectIdentifier): void
    {
        $list = $this->getSubjectList();

        if (\in_array($subjectIdentifier, $list, true)) {
            return;
        }

        $list[] = $subjectIdentifier;
        $this->saveSubjectList($list);
    }

    private function removeFromSubjectList(string $subjectIdentifier): void
    {
        $list = $this->getSubjectList();

        $key = \array_search($subjectIdentifier, $list, true);

        if (false === $key) {
            return;
        }

        unset($list[$key]);
        $this->saveSubjectList($list);
    }
}