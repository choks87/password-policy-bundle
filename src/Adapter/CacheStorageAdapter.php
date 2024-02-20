<?php
declare(strict_types=1);

namespace Choks\PasswordPolicy\Adapter;

use Choks\PasswordPolicy\Contract\PasswordPolicySubjectInterface;
use Choks\PasswordPolicy\Contract\StorageAdapterInterface;
use Choks\PasswordPolicy\Exception\RuntimeException;
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
            throw new RuntimeException('Failed to save cache item to password history.');
        }
    }

    public function remove(PasswordPolicySubjectInterface $subject): void
    {
        $this->cache->deleteItem($this->getCacheKey($subject));
    }

    public function getPastPasswords(
        PasswordPolicySubjectInterface $subject,
        ?int                           $lastN = null,
        ?\DateTimeImmutable            $startingFrom = null,
    ): iterable {
        $cacheItem = $this->cache->getItem($this->getCacheKey($subject));
        /** @var array<Item>|array{} $value */
        $value = $cacheItem->get() ?? [];
        $value = \array_reverse($value);

        foreach ($value as $index => $item) {
            if (null !== $startingFrom && $item['created_at'] < $startingFrom) {
                break;
            }

            if (null !== $lastN && ($index + 1) > $lastN) {
                break;
            }

            yield $item['password'];
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