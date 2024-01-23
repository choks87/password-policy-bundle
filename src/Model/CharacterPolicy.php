<?php

declare(strict_types=1);

namespace Choks\PasswordPolicy\Model;

use Choks\PasswordPolicy\Contract\CharacterPolicyInterface;

final class CharacterPolicy implements CharacterPolicyInterface
{
    public function __construct(
        private readonly ?int           $chars,
        private readonly ?int           $numChars,
        private readonly ?int           $lowercase,
        private readonly ?int           $uppercase,
        private readonly ?int           $special,
    ) {
    }

    public function getMinLength(): ?int
    {
        return $this->chars;
    }

    public function getMinNumerics(): ?int
    {
        return $this->numChars;
    }

    public function getMinLowercase(): ?int
    {
        return $this->lowercase;
    }

    public function getMinUppercase(): ?int
    {
        return $this->uppercase;
    }

    public function getMinSpecials(): ?int
    {
        return $this->special;
    }
}
