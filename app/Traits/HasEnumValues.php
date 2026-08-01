<?php

namespace App\Traits;

trait HasEnumValues
{
    /**
     * Get all the enum values.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
