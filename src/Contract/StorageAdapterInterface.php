<?php
declare(strict_types=1);

namespace Choks\PasswordPolicy\Contract;

use Choks\PasswordPolicy\Criteria\SearchCriteria;
use Choks\PasswordPolicy\Exception\StorageException;
use Choks\PasswordPolicy\ValueObject\Password;

interface StorageAdapterInterface
{
    /**
     * @throws StorageException
     */
    public function add(Password $password): void;

    /**
     * Clear all storage for subject
     * @throws StorageException
     */
    public function removeForSubject(PasswordPolicySubjectInterface $subject): void;

    /**
     * Method should support searching for stored passwords via Start Date, End Date of creation,
     * by subject and using limit
     * @throws StorageException
     *
     * @return iterable<Password>
     */
    public function get(SearchCriteria $criteria): iterable;

    /**
     * Clears all storage
     * @throws StorageException
     */
    public function clear(): void;
}