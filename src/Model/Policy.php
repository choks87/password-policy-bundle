<?php

declare(strict_types=1);

namespace Choks\PasswordPolicy\Model;

use Choks\PasswordPolicy\Contract\CharacterPolicyInterface;
use Choks\PasswordPolicy\Contract\HistoryPolicyInterface;
use Choks\PasswordPolicy\Contract\PolicyInterface;

final class Policy implements PolicyInterface
{
    public function __construct(
        private readonly ?CharacterPolicyInterface $characterPolicy,
        private readonly ?HistoryPolicyInterface   $historyPolicy,
    ) {
    }

    public function getHistoryPolicy(): ?HistoryPolicyInterface
    {
        return $this->historyPolicy;
    }

    public function getCharacterPolicy(): ?CharacterPolicyInterface
    {
        return $this->characterPolicy;
    }
}
