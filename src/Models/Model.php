<?php

namespace Models;

use Database\DatabaseOperations;

abstract class Model
{
    use DatabaseOperations;

    protected static string $table;

    public static function all()
    {
        return self::select(static::$table);
    }

    public static function find($id)
    {
        return self::select(static::$table, [
            'where' => ['id' => $id]
        ]);
    }

    public static function create(array $data)
    {
        return self::insert(static::$table, $data);
    }

    public static function updateRecord($id, array $data)
    {
        return self::update(static::$table, $data, ['id' => $id]);
    }

    public static function destroy($id)
    {
        return self::delete(static::$table, ['id' => $id]);
    }
}
