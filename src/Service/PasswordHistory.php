<?php

declare(strict_types=1);

namespace Choks\PasswordPolicy\Service;

use Choks\PasswordPolicy\Contract\PasswordPolicySubjectInterface;
use Choks\PasswordPolicy\Contract\StorageAdapterInterface;
use Choks\PasswordPolicy\Contract\HistoryPolicyInterface;
use Choks\PasswordPolicy\Contract\PasswordHistoryInterface;
use Choks\PasswordPolicy\Criteria\SearchCriteria;
use Choks\PasswordPolicy\Enum\Order;
use Choks\PasswordPolicy\Exception\InvalidArgumentException;
use Choks\PasswordPolicy\ValueObject\Password;

final class PasswordHistory implements PasswordHistoryInterface
{
    public function __construct(
        private readonly Crypt                   $crypt,
        private readonly StorageAdapterInterface $adapter,
    ) {
    }

    public function add(PasswordPolicySubjectInterface $subject): void
    {
        if (null === $subject->getPlainPassword()) {
            throw new InvalidArgumentException('Cannot add null password to history.');
        }
        $hashedPassword = $this->crypt->encrypt($subject->getPlainPassword());

        $this->adapter->add(new Password($subject->getIdentifier(), $hashedPassword));
    }

    public function remove(PasswordPolicySubjectInterface $subject): void
    {
        $this->adapter->removeForSubject($subject);
    }

    public function isUsed(PasswordPolicySubjectInterface $subject, HistoryPolicyInterface $historyPolicy): bool
    {
        if (null === $subject->getPlainPassword()) {
            throw new InvalidArgumentException('Cannot check for null password for previous usage.');
        }

        if (!$historyPolicy->isValid()) {
            return false;
        }

        $records = $this->fetch($subject, $historyPolicy);

        foreach ($records as $record) {
            if ($this->crypt->decrypt($record->getHashedPassword()) === $subject->getPlainPassword()) {
                return true;
            }
        }

        return false;
    }

    public function clear(): void
    {
        $this->adapter->clear();
    }

    /**
     * @return iterable<Password>
     */
    private function fetch(PasswordPolicySubjectInterface $subject, HistoryPolicyInterface $historyPolicy): iterable
    {
        $lastN        = $historyPolicy->getLast();
        $startingFrom = $historyPolicy->hasPeriod() ? $historyPolicy->getTimeInPast() : null;

        $criteria = (new SearchCriteria())
            ->setSubject($subject)
            ->setOrder(Order::DESC)
        ;

        if (null !== $lastN) {
            $criteria->setLimit($lastN);
        }

        if (null !== $startingFrom) {
            $criteria->setStartDate($startingFrom);
        }

        return $this->adapter->get($criteria);
    }

}