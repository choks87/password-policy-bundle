<?php
declare(strict_types=1);

namespace Choks\PasswordPolicy\Tests;

use Choks\PasswordPolicy\Criteria\SearchCriteria;
use Choks\PasswordPolicy\ValueObject\PasswordRecord;

trait AdapterTestTrait
{
    /**
     * @return string[]
     */
    private function getPasswords(SearchCriteria $criteria): array
    {
        return \array_map(
            static fn(PasswordRecord $record) => $record->getHashedPassword(),
            [...$this->storageAdapter->get($criteria)]
        );
    }
}