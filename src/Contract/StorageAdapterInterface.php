<?php
declare(strict_types=1);

namespace Choks\PasswordPolicy\Contract;

use Choks\PasswordPolicy\Criteria\SearchCriteria;
use Choks\PasswordPolicy\ValueObject\PasswordRecord;

interface StorageAdapterInterface
{
    public function add(PasswordPolicySubjectInterface $subject, string $hashedPassword): void;

    /**
     * Clear all storage for subject
     */
    public function remove(PasswordPolicySubjectInterface $subject): void;

    /**
     * Method should support searching for stored passwords via Start Date, End Date of creation,
     * by subject and using limit
     *
     * @return iterable<PasswordRecord>
     */
    public function get(SearchCriteria $criteria): iterable;

    /**
     * Clears all storage
     */
    public function clear(): void;
}