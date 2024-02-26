<?php
declare(strict_types=1);

namespace Choks\PasswordPolicy\Contract;

interface PolicyInterface
{
    public function getCharacterPolicy(): ?CharacterPolicyInterface;

    public function getHistoryPolicy(): ?HistoryPolicyInterface;

    public function getExpirationPolicy(): ?ExpirationPolicyInterface;
}