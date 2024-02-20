<?php

declare(strict_types=1);

namespace Choks\PasswordPolicy\Service;

use Choks\PasswordPolicy\Contract\PasswordPolicySubjectInterface;
use Choks\PasswordPolicy\Contract\StorageAdapterInterface;
use Choks\PasswordPolicy\Contract\HistoryPolicyInterface;
use Choks\PasswordPolicy\Contract\PasswordHistoryInterface;
use Choks\PasswordPolicy\Exception\InvalidArgumentException;

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

        $this->adapter->add($subject, $hashedPassword);
    }

    public function remove(PasswordPolicySubjectInterface $subject): void
    {
        $this->adapter->remove($subject);
    }

    public function isUsed(PasswordPolicySubjectInterface $subject, HistoryPolicyInterface $historyPolicy): bool
    {
        if (null === $subject->getPlainPassword()) {
            throw new InvalidArgumentException('Cannot check for null password for previous usage.');
        }

        if (!$historyPolicy->isValid()) {
            return false;
        }

        $pastPasswords = $this->fetch($subject, $historyPolicy);

        foreach ($pastPasswords as $pastPassword) {
            if ($this->crypt->decrypt($pastPassword) === $subject->getPlainPassword()) {
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
     * @return iterable<string>
     */
    private function fetch(PasswordPolicySubjectInterface $user, HistoryPolicyInterface $historyPolicy): iterable
    {
        $lastN        = $historyPolicy->getBackTrackCount();
        $startingFrom = $historyPolicy->hasPeriod() ? $historyPolicy->backTrackStartDateTime() : null;

        return $this->adapter->getPastPasswords($user, $lastN, $startingFrom);
    }

}