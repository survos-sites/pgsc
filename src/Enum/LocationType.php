<?php

namespace App\Enum;

enum LocationType: string
{
    case MUSEO = 'museo';
    case GALLERIA = 'galleria';
    case CC = 'cc';

    public static function choices(): array
    {
        return [
            'Museo' => self::MUSEO,
            'Galleria' => self::GALLERIA,
            'CC' => self::CC,
        ];
    }
}
