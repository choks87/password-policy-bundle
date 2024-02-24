<?php
declare(strict_types=1);

namespace Choks\PasswordPolicy\Tests\Service;

use Choks\PasswordPolicy\Adapter\ArrayStorageAdapter;
use Choks\PasswordPolicy\Contract\PolicyProviderInterface;
use Choks\PasswordPolicy\Contract\StorageAdapterInterface;
use Choks\PasswordPolicy\Event\ExpiredPasswordEvent;
use Choks\PasswordPolicy\Service\PasswordExpiration;
use Choks\PasswordPolicy\Tests\KernelTestCase;
use Choks\PasswordPolicy\Tests\Resources\App\Entity\Subject;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class PasswordExpirationTest extends KernelTestCase
{
    private ArrayStorageAdapter      $storageAdapter;
    private PasswordExpiration       $passwordExpiration;
    private PolicyProviderInterface  $policyProvider;

    protected function setUp(): void
    {
        $this->storageAdapter     = self::getContainer()->get(StorageAdapterInterface::class);
        $this->policyProvider     = self::getContainer()->get(PolicyProviderInterface::class);
        $this->passwordExpiration = self::getContainer()->get(PasswordExpiration::class);
        $this->storageAdapter->clear();
    }

    protected function tearDown(): void
    {
        $this->storageAdapter->clear();
    }

    public function testGetExpired(): void
    {
        $subject = new Subject(1);

        $list = &$this->storageAdapter->getListByReference();

        $dateInPast = (new \DateTimeImmutable())->sub(\DateInterval::createFromDateString('3 days'));

        $list[] = [
            'subject_id' => 'foo',
            'password'   => 'bar',
            'created_at' => $dateInPast,
        ];

        $list[] = [
            'subject_id' => 'waldoo',
            'password'   => 'fruit',
            'created_at' => new \DateTimeImmutable(),
        ];

        $expired = $this->passwordExpiration->getExpired($subject);

        self::assertEquals('foo', $expired->getSubjectIdentifier());
        self::assertEquals('bar', $expired->getHashedPassword());
        self::assertEquals($dateInPast, $expired->getCreatedAt());
    }

    public function testThereIsNoExpired(): void
    {
        $subject = new Subject(1);

        $list = &$this->storageAdapter->getListByReference();

        $list[] = [
            'subject_id' => 'foo',
            'password'   => 'bar',
            'created_at' => new \DateTimeImmutable(),
        ];

        $expired = $this->passwordExpiration->getExpired($subject);

        self::assertNull($expired);
    }

    public function testProcessExpired(): void
    {
        $subject = new Subject(1);

        $list = &$this->storageAdapter->getListByReference();

        $dateInPast        = new \DateTimeImmutable('2020-01-05 00:00:00');
        $expectedExpiredAt = new \DateTimeImmutable('2020-01-06 00:00:00');
        $list[]            = [
            'subject_id' => 'foo',
            'password'   => 'bar',
            'created_at' => $dateInPast,
        ];

        /** @var MockObject|EventDispatcherInterface $dispatcher */
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::callback(static function ($event) use ($expectedExpiredAt, $dateInPast): bool {
                if (!$event instanceof ExpiredPasswordEvent) {
                    return false;
                }

                if ($event->getCreatedAt() !== $dateInPast) {
                    return false;
                }

                if ($event->getExpiredAt()->getTimestamp() !== $expectedExpiredAt->getTimestamp()) {
                    return false;
                }

                return true;
            }))
        ;

        $passwordExpiration = new PasswordExpiration($this->storageAdapter, $this->policyProvider, $dispatcher);
        $passwordExpiration->processExpired($subject);

    }

}