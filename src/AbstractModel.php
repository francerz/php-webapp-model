<?php

namespace Francerz\WebappModelUtils;

use Francerz\SqlBuilder\DatabaseManager;
use Francerz\SqlBuilder\Query;
use Francerz\SqlBuilder\SelectQuery;

abstract class AbstractModel
{
    /**
     * @return string
     */
    abstract public static function getDatabase(): string;

    /**
     * @return string
     */
    abstract public static function getTableName(): string;

    /**
     * @return string[]
     */
    abstract public static function getPrimaryKey(): array;

    /**
     * @return string[]
     */
    abstract public static function getColumnNames(): array;

    /**
     * @param array $params
     * @return SelectQuery
     */
    abstract public static function getQuery(array $params = []): SelectQuery;

    /**
     * @param array $params
     * @return static[]
     */
    public static function getRows(array $params = [])
    {
        $db = DatabaseManager::connect(static::getDatabase());
        $query = static::getQuery($params);
        $result = $db->executeSelect($query);
        return $result->toArray(static::class);
    }

    /**
     * @param array $params
     * @return static
     */
    public static function getFirst(array $params = [])
    {
        $rows = static::getRows();
        return reset($rows) ?: null;
    }

    /**
     * @param array $params
     * @return static
     */
    public static function getLast(array $params = [])
    {
        $rows = static::getRows();
        return end($rows) ?: null;
    }

    public static function insert($data, ?array $columns = null)
    {
        $db = DatabaseManager::connect(static::getDatabase());
        $query = Query::insertInto(static::getTableName(), $data, $columns);
        $result = $db->executeInsert($query);
        return $result;
    }

    public static function update($data, ?array $keys = null, ?array $columns = null)
    {
        $db = DatabaseManager::connect(static::getDatabase());
        $query = Query::update(static::getTableName(), $data, $keys, $columns);
        $result = $db->executeUpdate($query);
        return $result;
    }

    public static function upsert($data, ?array $keys = null, ?array $columns = null)
    {
        $db = DatabaseManager::connect(static::getDatabase());
        $query = Query::upsert(static::getTableName(), $data, $keys, $columns);
        $result = $db->executeUpsert($query);
        return $result;
    }

    public static function upsertMany(array $data, ?array $keys = null, ?array $columns = null)
    {
        $db = DatabaseManager::connect(static::getDatabase());
        $query = Query::upsert(static::getTableName(), $data, $keys, $columns);
        $result = $db->executeUpsert($query);
        return $result;
    }
}
