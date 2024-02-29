<?php
declare(strict_types=1);

namespace Choks\PasswordPolicy\Tests;

use Choks\PasswordPolicy\Criteria\SearchCriteria;
use Choks\PasswordPolicy\ValueObject\Password;

trait AdapterTestTrait
{
    /**
     * @return string[]
     */
    private function getPasswords(SearchCriteria $criteria): array
    {
        $passwords = [...$this->storageAdapter->get($criteria)];
        return \array_map(
            static fn(Password $password) => $password->getHashedPassword(),
            [...$this->storageAdapter->get($criteria)]
        );
    }
}