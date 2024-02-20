<?php
declare(strict_types=1);

namespace Choks\PasswordPolicy\Tests\Adapter;

use Choks\PasswordPolicy\Adapter\CacheStorageAdapter;
use Choks\PasswordPolicy\Contract\StorageAdapterInterface;
use Choks\PasswordPolicy\Tests\KernelTestCase;
use Choks\PasswordPolicy\Tests\Resources\App\Entity\Subject;
use Symfony\Component\Cache\Adapter\AdapterInterface;

final class CacheAdapterTest extends KernelTestCase
{
    private StorageAdapterInterface $storageAdapter;

    private AdapterInterface $cache;

    protected function setUp(): void
    {
        $this->cache          = self::getContainer()->get('cache.app');
        $this->storageAdapter = new CacheStorageAdapter($this->cache, 'foo');
    }

    public function testAdd(): void
    {
        self::assertFalse($this->cache->hasItem('foo_1'));

        $this->storageAdapter->add(new Subject(1, 'bar'), 'baz');

        self::assertTrue($this->cache->hasItem('foo_1'));
    }

    public function testRemove(): void
    {
        $subject = new Subject(1, 'bar');

        $this->storageAdapter->add($subject, 'baz');
        self::assertTrue($this->cache->hasItem('foo_1'));
        $this->storageAdapter->remove($subject);
        self::assertFalse($this->cache->hasItem('foo_1'));

    }

    public function testGetPastPasswords(): void
    {
        $subject = new Subject(1, 'bar');

        $this->storageAdapter->add($subject, 'baz');
        $this->storageAdapter->add($subject, 'waldoo');
        $this->storageAdapter->add($subject, 'fruit');
        $expected = ['fruit', 'waldoo', 'baz'];
        self::assertEquals($expected, [...$this->storageAdapter->getPastPasswords($subject)]);
    }

    public function testGetPastNPasswords(): void
    {
        $subject = new Subject(1, 'bar');

        $this->storageAdapter->add($subject, 'baz');
        $this->storageAdapter->add($subject, 'waldoo');
        $this->storageAdapter->add($subject, 'fruit');

        $expected = ['fruit', 'waldoo'];
        self::assertEquals($expected, [...$this->storageAdapter->getPastPasswords($subject, 2)]);
    }

    public function testGetPastPasswordsWithStartingFrom(): void
    {
        $subject = new Subject(1, 'bar');

        $cacheItem = $this->cache->getItem('foo_1');
        $cacheItem->set([
                            [
                                'subject'    => $subject->getIdentifier(),
                                'password'   => 'baz',
                                'created_at' => new \DateTimeImmutable('-2 hour'),
                            ],
                            [
                                'subject'    => $subject->getIdentifier(),
                                'password'   => 'waldoo',
                                'created_at' => new \DateTimeImmutable('-1 hour'),
                            ],
                            [
                                'subject'    => $subject->getIdentifier(),
                                'password'   => 'fruit',
                                'created_at' => new \DateTimeImmutable('-30 minutes'),
                            ],
                        ]);
        $this->cache->save($cacheItem);

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
}