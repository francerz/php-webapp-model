<?php

namespace Francerz\WebappModelUtils;

use Francerz\SqlBuilder\Results\DeleteResult;
use Francerz\SqlBuilder\Results\InsertResult;
use Francerz\SqlBuilder\Results\UpdateResult;
use Francerz\SqlBuilder\Results\UpsertResult;
use Francerz\SqlBuilder\SelectQuery;

abstract class AbstractModel implements ModelInterface
{
    #region Model operations static methods
    /**
     * Builds a SelectQuery object based upon given parameters.
     *
     * This method checks $params utilization to ensure proper query building.
     *
     * @param array $params
     * An associative array with select query building parameters.
     * This function also includes some default building parameters:
     *  - **@orderBy**: Applies `ORDER BY` clause to select query.
     *  - **@limit**: Restricts number of items returned by SelectQuery.
     *  - **@offset**: Available only with `@limit` parameter, skips rows a the beginning of result set.
     *  - **@page**: Retrieves a chunk of records from result set.
     *  - **@pageSize**: Sets the size of records chunk.
     *
     * @return SelectQuery
     *
     * @throws UnusedParamsException If any of given parameters weren't properly used in select query building.
     */
    final public static function getQuery(array $params = []): SelectQuery
    {
        return ModelOperations::getQuery(static::class, $params);
    }

    /**
     * Retrieves an array of model instances that are result of params
     *
     * @param array $params An associative array with select query building parameters.
     *
     * @return static[]
     */
    final public static function getRows(array $params = [])
    {
        return ModelOperations::getRows(static::class, $params);
    }

    /**
     * Retrieves the first item from a query result set.
     *
     * @param array $params An associative array with select query building parameters.
     * @return static
     */
    final public static function getFirst(array $params = [])
    {
        return ModelOperations::getFirst(static::class, $params);
    }

    /**
     * Retrieves the last item from a query result set.
     *
     * @param array $params An associative array with select query building parameters.
     * @return static
     */
    final public static function getLast(array $params = [])
    {
        return ModelOperations::getLast(static::class, $params);
    }

    /**
     * Performs an insert into model table with given data and column names.
     *
     * @param static $data A model class instance with required attribute values.
     * @param string[] $columns A list of column names to be inserted.
     * @return InsertResult
     */
    final public static function insert($data, array $columns)
    {
        return ModelOperations::insert(static::class, $data, $columns);
    }

    /**
     * Performs an insert into model table from a collection of data with column names.
     *
     * @param iterable $data A collection of model class instances.
     * @param string[] $columns A list of column names to be inserted.
     * @return InsertResult
     */
    final public static function insertMany(iterable $data, array $columns)
    {
        return ModelOperations::insertMany(static::class, $data, $columns);
    }

    /**
     * Performs an update operation in model table from given data object
     * where column matches with $keys argument.
     *
     * @param static $data A model class instance with data to be updated.
     * @param string[] $keys Names of columns that should match to update.
     * @param string[] $columns Column names to be updated.
     * @return UpdateResult
     */
    final public static function update($data, array $keys, array $columns)
    {
        return ModelOperations::update(static::class, $data, $keys, $columns);
    }

    /**
     * Performs an update in all matching rows, otherwise rows will be inserted.
     *
     * If columns is empty, the update operation is ignored and only inserts
     * when no matching row is found.
     *
     * @param static $data A model class instance with data to insert or update.
     * @param string[] $keys Names of columns that should match to update.
     * @param string[] $columns Column names to be updated or inserted.
     * @return UpsertResult
     */
    final public static function upsert($data, array $keys, array $columns = [])
    {
        return ModelOperations::upsert(static::class, $data, $keys, $columns);
    }

    /**
     * Performs an update in all matching rows, otherwise rows will be inserted.
     *
     * @param iterable $data A collection of model class instances with data to insert or update.
     * @param string[] $keys Names of columns that should match to update.
     * @param string[] $columns Column names to be updated or inserted.
     * @return UpsertResult
     */
    final public static function upsertMany(iterable $data, array $keys, array $columns)
    {
        return ModelOperations::upsertMany(static::class, $data, $keys, $columns);
    }

    /**
     * Deletes rows in table based upon given filter arguments.
     *
     * @param array $filter
     * @return DeleteResult
     */
    final public static function delete(array $filter)
    {
        return ModelOperations::delete(static::class, $filter);
    }
    #endregion
}
