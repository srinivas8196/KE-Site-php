<?php

namespace Models;

class User extends Model
{
    protected static string $table = 'users';

    public static function findByUsername(string $username)
    {
        return self::select(self::$table, [
            'where' => ['username' => $username]
        ]);
    }

    public static function createUser(array $data)
    {
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        return self::create($data);
    }
}
