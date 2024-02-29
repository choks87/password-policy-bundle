<?php
declare(strict_types=1);

namespace Choks\PasswordPolicy\Service;

use Choks\PasswordPolicy\Contract\ExpirationPolicyInterface;
use Choks\PasswordPolicy\Contract\PasswordExpirationInterface;
use Choks\PasswordPolicy\Contract\PasswordPolicySubjectInterface;
use Choks\PasswordPolicy\Contract\PolicyProviderInterface;
use Choks\PasswordPolicy\Contract\StorageAdapterInterface;
use Choks\PasswordPolicy\Criteria\SearchCriteria;
use Choks\PasswordPolicy\Enum\Order;
use Choks\PasswordPolicy\Event\ExpiredPasswordEvent;
use Choks\PasswordPolicy\ValueObject\Password;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class PasswordExpiration implements PasswordExpirationInterface
{
    public function __construct(
        private readonly StorageAdapterInterface  $storageAdapter,
        private readonly PolicyProviderInterface  $policyProvider,
        private readonly EventDispatcherInterface $dispatcher,
    ) {
    }

    public function getExpired(PasswordPolicySubjectInterface $subject): ?Password
    {
        $policy = $this->policyProvider->getPolicy($subject)->getExpirationPolicy();
        if (null === $policy || !$policy->hasPeriod()) {
            return null;
        }

        $criteria = new SearchCriteria();
        $criteria
            ->setLimit(1)
            ->setOrder(Order::DESC)
        ;

        $latestPasswords = [...$this->storageAdapter->get($criteria)];
        if (\count($latestPasswords) === 0) {
            return null;
        }

        /** @var Password $latestPassword */
        $latestPassword = \reset($latestPasswords);

        return $latestPassword->getCreatedAt() <= $policy->getTimeInPast() ? $latestPassword : null;
    }

    public function processExpired(PasswordPolicySubjectInterface $subject): void
    {
        $expired = $this->getExpired($subject);

        if (null === $expired) {
            return;
        }

        /** @var ExpirationPolicyInterface $policy */
        $policy = $this->policyProvider->getPolicy($subject)->getExpirationPolicy();

        /** @var \DateInterval $interval */
        $interval = \DateInterval::createFromDateString(
            \sprintf('%d %s', $policy->getPeriod(), $policy->getUnit()
            )
        );

        $event = new ExpiredPasswordEvent(
            $subject->getIdentifier(),
            $expired->getCreatedAt()->add($interval),
            $expired->getCreatedAt()
        );

        $this->dispatcher->dispatch($event);
    }
}