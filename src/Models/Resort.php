<?php

namespace Models;

class Resort extends Model
{
    protected static string $table = 'resorts';

    // Add custom methods specific to resorts
    public static function findByDestination($destinationId)
    {
        return self::select(self::$table, [
            'where' => ['destination_id' => $destinationId],
            'order' => ['resort_name' => 'asc']
        ]);
    }
}
