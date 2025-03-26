<?php

namespace Models;

class Destination extends Model
{
    protected static string $table = 'destinations';

    public static function getWithResorts()
    {
        return self::table(self::$table)
            ->select('*, resorts(*)')
            ->execute();
    }
}
