<?php
declare(strict_types=1);

namespace Choks\PasswordPolicy\Enum;

enum PeriodUnit: string
{
    case DAY   = 'day';
    case WEEK  = 'week';
    case MONTH = 'month';
    case YEAR  = 'year';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return \array_column(self::cases(), 'value');
    }
}
