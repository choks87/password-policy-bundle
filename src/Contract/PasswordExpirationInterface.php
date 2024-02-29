<?php
declare(strict_types=1);

namespace Choks\PasswordPolicy\Contract;

use Choks\PasswordPolicy\Exception\StorageException;
use Choks\PasswordPolicy\ValueObject\Password;
use Choks\PasswordPolicy\Exception\RuntimeException;

interface PasswordExpirationInterface
{
    /**
     * @throws StorageException
     * @throws RuntimeException
     */
    public function getExpired(PasswordPolicySubjectInterface $subject): ?Password;

    /**
     * @throws StorageException
     * @throws RuntimeException
     */
    public function processExpired(PasswordPolicySubjectInterface $subject): void;
}