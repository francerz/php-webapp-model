<?php

namespace Francerz\WebappModelUtils;

use Francerz\SqlBuilder\Components\Table;
use Francerz\SqlBuilder\DatabaseManager;
use Francerz\SqlBuilder\Query;
use Francerz\SqlBuilder\Results\DeleteResult;
use Francerz\SqlBuilder\Results\InsertResult;
use Francerz\SqlBuilder\Results\UpdateResult;
use Francerz\SqlBuilder\Results\UpsertResult;
use Francerz\SqlBuilder\SelectQuery;
use InvalidArgumentException;

abstract class AbstractModel
{
    /**
     * Model Descriptor
     *
     * @return ModelDescriptor
     */
    abstract public static function getModelDescriptor(): ModelDescriptor;

    /**
     * Builds a SelectQuery object based upon given parameters.
     *
     * This method receives a ModelParams instance to ensure query building consistency.
     *
     * This method is used by `getQuery()`, `getRows()`, `getFirst()` and `getLast()` methods.
     *
     * @return SelectQuery
     */
    abstract public static function buildSelectQuery(SelectQuery $query, ModelParams $params): SelectQuery;

    #region Model operations
    /**
     * Builds a SelectQuery object based upon given parameters.
     *
     * This method checks $params utilization to ensure proper query building.
     *
     * @param array $params
     * An associative array with select query building parameters.
     * This function also includes some default building parameters:
     *  - **`@orderBy`**: Applies `ORDER BY` clause to select query.
     *  - **`@limit`**: Restricts number of items returned by SelectQuery.
     *  - **`@offset`**: Available only with `@limit` parameter, skips rows a the beginning of result set.
     *  - **`@page`**: Retrieves a chunk of records from result set.
     *  - **`@pageSize`**: Sets the size of records chunk.
     *
     * @return SelectQuery
     *
     * @throws UnusedParamsException If any of given parameters weren't properly used in select query building.
     */
    public static function getQuery(array $params = []): SelectQuery
    {
        // Wraps $params parameter to check select query building consistency.
        $params = new ModelParams($params);

        $modelDescriptor = static::getModelDescriptor();
        $tableRef = new Table($modelDescriptor->getTableName(), $modelDescriptor->getTableAlias());
        $query = Query::selectFrom($tableRef);

        // Builds select query from given params.
        $query = static::buildSelectQuery($query, $params);

        // Applies @orderBy parameter to SelectQuery.
        if (isset($params['@orderBy'])) {
            $query->orderBy($params['@orderBy']);
        }

        // Applies @limit parameter to SelectQuery.
        // Optional @offset parameter may be used to skip rows (default = 0).
        if (isset($params['@limit'])) {
            $offset = isset($params['@offset']) ? $params['@offset'] : 0;
            $query->limit($params['@limit'], $offset);
        }

        // Applies @page parameter to SelectQuery to handle result in chunks.
        // Optional @pageSize parameter may be used to modify chunk size (default = 500).
        if (isset($params['@page'])) {
            $pageSize = isset($params['@pageSize']) ? $params['@pageSize'] : 500;
            $query->paginate($params['@page'], $pageSize);
        }

        // Checks if all params were used properly.
        $params->checkUsed();
        return $query;
    }

    /**
     * Retrieves an array of model instances that are result of params
     *
     * @param array $params An associative array with select query building parameters.
     *
     * @return static[]
     */
    public static function getRows(array $params = [])
    {
        $modelDescriptor = static::getModelDescriptor();
        $db = DatabaseManager::connect($modelDescriptor->getDatabase());
        $query = static::getQuery($params);
        $result = $db->executeSelect($query);
        return $result->toArray(static::class);
    }

    /**
     * Retrieves the first item from a query result set.
     *
     * @param array $params An associative array with select query building parameters.
     * @return static
     */
    public static function getFirst(array $params = [])
    {
        $rows = static::getRows($params);
        return reset($rows) ?: null;
    }

    /**
     * Retrieves the last item from a query result set.
     *
     * @param array $params An associative array with select query building parameters.
     * @return static
     */
    public static function getLast(array $params = [])
    {
        $rows = static::getRows($params);
        return end($rows) ?: null;
    }

    /**
     * Retrieves the Primary Key name when there's only one. Otherwise returns null.
     *
     * @return string|null Primary key name string.
     */
    public static function getSinglePrimaryKeyName(): ?string
    {
        $modelDescriptor = static::getModelDescriptor();
        $pks = $modelDescriptor->getPrimaryKeyNames();
        return count($pks) === 1 ? reset($pks) : null;
    }

    /**
     * Performs an insert into model table with given data and column names.
     *
     * @param static $data A model class instance with required attribute values.
     * @param string[] $columns A list of column names to be inserted.
     * @return InsertResult
     */
    public static function insert(object $data, array $columns): InsertResult
    {
        if (!$data instanceof static) {
            throw new InvalidArgumentException(sprintf('Argument $data must be of type %s.', static::class));
        }

        $modelDescriptor = static::getModelDescriptor();
        $db = DatabaseManager::connect($modelDescriptor->getDatabase());
        $query = Query::insertInto($modelDescriptor->getTableName(), $data, $columns);
        $result = $db->executeInsert($query);

        if (
            ($pk = static::getSinglePrimaryKeyName()) &&
            ($insertedId = $result->getInsertedId())
        ) {
            $data->{$pk} = $insertedId;
        }

        return $result;
    }

    /**
     * Performs an insert into model table from a collection of data with column names.
     *
     * @param iterable $data A collection of model class instances.
     * @param string[] $columns A list of column names to be inserted.
     * @return InsertResult
     */
    public static function insertMany(iterable $data, array $columns): InsertResult
    {
        foreach ($data as $k => $v) {
            if (!$v instanceof static) {
                throw new InvalidArgumentException(
                    sprintf('Invalid item type in $data[%d], must be of type %s.', $k, static::class)
                );
            }
        }

        $modelDescriptor = static::getModelDescriptor();
        $db = DatabaseManager::connect($modelDescriptor->getDatabase());
        $query = Query::insertInto($modelDescriptor->getTableName(), $data, $columns);
        $result = $db->executeInsert($query);

        if (
            ($pk = static::getSinglePrimaryKeyName()) &&
            ($insertedId = $result->getInsertedId())
        ) {
            foreach ($data as $v) {
                $v->{$pk} = $insertedId++;
            }
        }

        return $result;
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
    public static function update(object $data, array $keys, array $columns): UpdateResult
    {
        if (!$data instanceof static) {
            throw new InvalidArgumentException(sprintf('Argument $data must be of type %s.', static::class));
        }

        $modelDescriptor = static::getModelDescriptor();
        $db = DatabaseManager::connect($modelDescriptor->getDatabase());
        $query = Query::update($modelDescriptor->getTableName(), $data, $keys, $columns);
        $result = $db->executeUpdate($query);
        return $result;
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
    public static function upsert(object $data, array $keys, array $columns = []): UpsertResult
    {
        if (!$data instanceof static) {
            throw new InvalidArgumentException(sprintf('Argument $data must be of type %s.', static::class));
        }

        $modelDescriptor = static::getModelDescriptor();
        $db = DatabaseManager::connect($modelDescriptor->getDatabase());
        $query = Query::upsert($modelDescriptor->getTableName(), $data, $keys, $columns);
        $result = $db->executeUpsert($query);

        if (
            ($pk = static::getSinglePrimaryKeyName()) &&
            ($insertedId = $result->getInsertedId())
        ) {
            foreach ($result->getInserts() as $ins) {
                $ins->{$pk} = $insertedId++;
            }
        }

        return $result;
    }

    /**
     * Performs an update in all matching rows, otherwise rows will be inserted.
     *
     * @param iterable $data A collection of model class instances with data to insert or update.
     * @param string[] $keys Names of columns that should match to update.
     * @param string[] $columns Column names to be updated or inserted.
     * @return UpsertResult
     */
    public static function upsertMany(iterable $data, array $keys, array $columns): UpsertResult
    {
        foreach ($data as $k => $v) {
            if (!$v instanceof static) {
                throw new InvalidArgumentException(
                    sprintf('Invalid item type in $data[%d], must be of type %s.', $k, static::class)
                );
            }
        }

        $modelDescriptor = static::getModelDescriptor();
        $db = DatabaseManager::connect($modelDescriptor->getDatabase());
        $query = Query::upsert($modelDescriptor->getTableName(), $data, $keys, $columns);
        $result = $db->executeUpsert($query);

        if (
            ($pk = static::getSinglePrimaryKeyName()) &&
            ($insertedId = $result->getInsertedId())
        ) {
            foreach ($result->getInserts() as $ins) {
                $ins->{$pk} = $insertedId++;
            }
        }

        return $result;
    }

    /**
     * Deletes rows in table based upon given filter arguments.
     *
     * @param array $filter
     * @return DeleteResult
     */
    public static function delete(array $filter): DeleteResult
    {
        $modelDescriptor = static::getModelDescriptor();
        $db = DatabaseManager::connect($modelDescriptor->getDatabase());
        $query = Query::deleteFrom($modelDescriptor->getTableName(), $filter);
        $result = $db->executeDelete($query);
        return $result;
    }
    #endregion
}
