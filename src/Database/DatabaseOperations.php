<?php

namespace Database;

trait DatabaseOperations 
{
    protected static function getSupabaseClient() 
    {
        return SupabaseConnection::getClient();
    }

    protected static function table(string $table)
    {
        return self::getSupabaseClient()->from($table);
    }

    protected static function select(string $table, array $options = [])
    {
        $query = self::table($table)->select('*');
        
        if (isset($options['where'])) {
            foreach ($options['where'] as $column => $value) {
                $query->eq($column, $value);
            }
        }

        if (isset($options['order'])) {
            $query->order($options['order']);
        }

        return $query->execute();
    }

    protected static function insert(string $table, array $data)
    {
        return self::table($table)
            ->insert($data)
            ->execute();
    }

    protected static function update(string $table, array $data, array $where)
    {
        $query = self::table($table);
        
        foreach ($where as $column => $value) {
            $query->eq($column, $value);
        }

        return $query->update($data)->execute();
    }

    protected static function delete(string $table, array $where)
    {
        $query = self::table($table);
        
        foreach ($where as $column => $value) {
            $query->eq($column, $value);
        }

        return $query->delete()->execute();
    }
}
