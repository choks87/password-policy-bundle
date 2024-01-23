<?php
declare(strict_types=1);

namespace Choks\PasswordPolicy\Contract;

interface CharacterPolicyInterface
{
    /**
     * Minimum Required characters
     */
    public function getMinLength(): ?int;

    /**
     * Minimum Numeric characters
     */
    public function getMinNumerics(): ?int;

    /**
     * Minimum Lowercase characters
     */
    public function getMinLowercase(): ?int;

    /**
     * Minimum Uppercase characters
     */
    public function getMinUppercase(): ?int;

    /**
     * Minimum Special characters
     */
    public function getMinSpecials(): ?int;
}