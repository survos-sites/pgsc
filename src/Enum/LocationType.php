<?php

namespace App\Enum;

enum LocationType: string
{
    case MUSEO = 'museo';
    case GALLERIA = 'restaurant';
    case REST = 'galeria';
    case CC = 'cc'; // cultural center

    public static function choices(): array
    {
        return [
            'Museo' => self::MUSEO,
            'Galleria' => self::GALLERIA,
            'CC' => self::CC,
            'REST' => self::REST,
        ];
    }

}
