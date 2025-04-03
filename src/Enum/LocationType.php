<?php

namespace App\Enum;

enum LocationType: string
{
    case MUSEO = 'museo';
    case GALLERIA = 'restaurant';
    case REST = 'galeria';
    case CC = 'cc'; // cultural center
    case TIENDA = 'tienda'; // cultural center
    case CAFE = 'cafeteria'; // cultural center
    case INST = 'instituto'; // cultural center

    public static function choices(): array
    {
        return [
            'Museo' => self::MUSEO,
            'Galleria' => self::GALLERIA,
            'CC' => self::CC,
            'Tienda' => self::TIENDA,
            'Cafeteria' => self::CAFE,
            'Instituto' => self::INST,
            'REST' => self::REST,
        ];
    }
}
