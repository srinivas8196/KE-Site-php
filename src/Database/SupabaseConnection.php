<?php

namespace Database;

use Supabase\CreateClient;
use Supabase\SupabaseClient;

class SupabaseConnection
{
    private static ?SupabaseClient $client = null;

    public static function getClient(): SupabaseClient
    {
        if (self::$client === null) {
            $config = require __DIR__ . '/../../config/database.php';
            
            self::$client = new CreateClient(
                $config['supabase']['url'],
                $config['supabase']['key']
            );
        }

        return self::$client;
    }
}
