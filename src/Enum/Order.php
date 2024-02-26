<?php
declare(strict_types=1);

namespace Choks\PasswordPolicy\Enum;

enum Order: string
{
    case ASC   = 'asc';
    case DESC  = 'desc';
}
