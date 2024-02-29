<?php
declare(strict_types=1);

namespace Choks\PasswordPolicy\Tests\Adapter;

use Choks\PasswordPolicy\Adapter\CacheStorageAdapter;
use Choks\PasswordPolicy\Contract\StorageAdapterInterface;
use Choks\PasswordPolicy\Criteria\SearchCriteria;
use Choks\PasswordPolicy\Enum\Order;
use Choks\PasswordPolicy\Tests\AdapterTestTrait;
use Choks\PasswordPolicy\Tests\KernelTestCase;
use Choks\PasswordPolicy\Tests\Resources\App\Entity\Subject;
use Choks\PasswordPolicy\ValueObject\Password;
use Symfony\Component\Cache\Adapter\AdapterInterface;

final class CacheAdapterTest extends KernelTestCase
{
    use AdapterTestTrait;

    private StorageAdapterInterface $storageAdapter;

    private AdapterInterface $cache;

    protected function setUp(): void
    {
        $this->cache          = self::getContainer()->get('cache.app');
        $this->storageAdapter = new CacheStorageAdapter($this->cache, 'foo');
    }

    public function testAdd(): void
    {
        self::assertFalse($this->cache->hasItem('foo_bar'));

        $this->storageAdapter->add(new Password('bar', 'baz'));

        self::assertTrue($this->cache->hasItem('foo_bar'));
    }

    public function testRemove(): void
    {
        $subject = new Subject('bar', 'baz');

        $this->storageAdapter->add(new Password($subject->getIdentifier(), 'waldoo'));
        self::assertTrue($this->cache->hasItem('foo_bar'));
        $this->storageAdapter->removeForSubject($subject);
        self::assertFalse($this->cache->hasItem('foo_bar'));

    }

    public function testGetPastPasswords(): void
    {
        $subject = new Subject(1, 'bar');

        $this->storageAdapter->add(new Password($subject->getIdentifier(), 'baz'));
        $this->storageAdapter->add(new Password($subject->getIdentifier(), 'waldoo'));
        $this->storageAdapter->add(new Password($subject->getIdentifier(), 'fruit'));
        $expected = ['fruit', 'waldoo', 'baz'];
        $criteria = (new SearchCriteria())->setSubject($subject)->setOrder(Order::DESC);

        self::assertEquals($expected, $this->getPasswords($criteria));
    }

    public function testGetPastNPasswords(): void
    {
        $subject = new Subject(1, 'bar');

        $this->storageAdapter->add(new Password($subject->getIdentifier(), 'baz'));
        $this->storageAdapter->add(new Password($subject->getIdentifier(), 'waldoo'));
        $this->storageAdapter->add(new Password($subject->getIdentifier(), 'fruit'));

        $expected = ['baz', 'waldoo'];
        $criteria = (new SearchCriteria())->setSubject($subject)->setLimit(2);

        self::assertEquals($expected, $this->getPasswords($criteria));
    }

    public function testGetPastPasswordsWithStartingFrom(): void
    {
        $subject = new Subject('bar', 'baz');

        $cacheItem = $this->cache->getItem('foo_bar');
        $cacheItem->set([
                            new Password(
                                $subject->getIdentifier(),
                                'baz',
                                new \DateTimeImmutable('-2 hour'),
                            ),
                            new Password(
                                $subject->getIdentifier(),
                                'waldoo',
                                new \DateTimeImmutable('-1 hour'),
                            ),
                            new Password(
                                $subject->getIdentifier(),
                                'fruit',
                                new \DateTimeImmutable('-30 minutes'),
                            ),
                        ]);
        $this->cache->save($cacheItem);

        $expected = ['fruit'];
        $criteria = (new SearchCriteria())
            ->setSubject($subject)
            ->setStartDate(new \DateTimeImmutable('-40 minutes'))
            ->setLimit(2)
        ;
        self::assertEquals($expected, $this->getPasswords($criteria));
    }

    public function testClear(): void
    {
        $subject = new Subject('bar', 'baz');

        $this->storageAdapter->add(new Password($subject->getIdentifier(), 'baz'));
        $this->storageAdapter->add(new Password($subject->getIdentifier(), 'waldoo'));
        $this->storageAdapter->add(new Password($subject->getIdentifier(), 'fruit'));

        $criteria = (new SearchCriteria())->setSubject($subject);

        self::assertNotEmpty($this->getPasswords($criteria));

        $this->storageAdapter->clear();

        self::assertEmpty($this->getPasswords($criteria));
    }
}