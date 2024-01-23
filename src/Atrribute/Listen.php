<?php
declare(strict_types=1);

namespace Choks\PasswordPolicy\Atrribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class Listen
{
    public function __construct()
    {
    }
}