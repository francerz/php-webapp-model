<?php

namespace Francerz\WebappModelUtils;

use Francerz\SqlBuilder\Components\Table;
use Francerz\SqlBuilder\DatabaseManager;
use Francerz\SqlBuilder\Query;
use Francerz\SqlBuilder\Results\DeleteResult;
use Francerz\SqlBuilder\SelectQuery;
use InvalidArgumentException;

/**
 * @deprecated
 */
abstract class ModelOperations
{
    /**
     * Returns `true` if a class implements the interface. Or `false` if class
     * do not implement it.
     *
     * @param string $class A class name string.
     * @param string $interface
     * @return bool
     */
    private static function classImplements($class, $interface)
    {
        return in_array($interface, class_implements($class));
    }

    /**
     * Checks if class implements an interface, throws an exception if is not
     * implemented.
     *
     * @param string $class A class name string.
     * @param srring $interface
     * @return void
     *
     * @throws InvalidArgumentException
     */
    private static function checkClassImplements($class, $interface)
    {
        if (!static::classImplements($class, $interface)) {
            throw new InvalidArgumentException(
                sprintf('The class %s must implement \'%s\'', $class, $interface)
            );
        }
    }

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
     *  - **@pageSize**: Sets the size of records chunk. (default = 500)
     *
     * @return SelectQuery
     *
     * @throws UnusedParamsException If any of given parameters weren't properly used in select query building.
     */
    final public static function getQuery($class, array $params = []): SelectQuery
    {
        static::checkClassImplements($class, ModelInterface::class);

        // Wraps $params parameter to check select query building consistency.
        $params = new ModelParams($params);

        /** @var ModelDescriptor */
        $modelDescriptor = $class::getModelDescriptor();
        $tableRef = new Table($modelDescriptor->getTableName(), $modelDescriptor->getTableAlias());
        $query = Query::selectFrom($tableRef);

        // Builds select query from given params.
        $query = $class::buildSelectQuery($query, $params);

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
     * @return object[]
     */
    final public static function getRows($class, array $params = [])
    {
        static::checkClassImplements($class, ModelInterface::class);
        /** @var ModelDescriptor */
        $modelDescriptor = $class::getModelDescriptor();
        $db = DatabaseManager::connect($modelDescriptor->getDatabase());
        $query = $class::getQuery($params); // DANGER: $class might not implement method `getQuery`.
        $result = $db->executeSelect($query);
        return $result->toArray($class);
    }

    /**
     * Retrieves the first item from a query result set.
     *
     * @param array $params An associative array with select query building parameters.
     * @return object|null
     */
    final public static function getFirst($class, array $params = [])
    {
        $rows = static::getRows($class, $params);
        return reset($rows) ?: null;
    }

    /**
     * Retrieves the last item from a query result set.
     *
     * @param array $params An associative array with select query building parameters.
     * @return object|null
     */
    final public static function getLast($class, array $params = [])
    {
        $rows = static::getRows($class, $params);
        return end($rows) ?: null;
    }

    /**
     * Retrieves the Primary Key name when there's only one. Otherwise returns null.
     *
     * @return string|null Primary key name string.
     */
    public static function getSinglePrimaryKeyName($class): ?string
    {
        static::checkClassImplements($class, ModelInterface::class);
        /** @var ModelDescriptor */
        $modelDescriptor = $class::getModelDescriptor();
        $pks = $modelDescriptor->getPrimaryKeyNames();
        if (count($pks) === 1) {
            return reset($pks);
        }
        return null;
    }

    /**
     * Performs an insert into model table with given data and column names.
     *
     * @param string $class A class name string.
     * @param object $data A model class instance with required attribute values.
     * @param string[] $columns A list of column names to be inserted.
     * @return InsertResult
     */
    final public static function insert($class, object $data, array $columns)
    {
        static::checkClassImplements($class, ModelInterface::class);
        if (!$data instanceof $class) {
            throw new InvalidArgumentException(sprintf('Argument $data must be of type %s.', $class));
        }

        /** @var ModelDescriptor */
        $modelDescriptor = $class::getModelDescriptor();
        $db = DatabaseManager::connect($modelDescriptor->getDatabase());
        $query = Query::insertInto($modelDescriptor->getTableName(), $data, $columns);
        $result = $db->executeInsert($query);

        if (
            ($pk = static::getSinglePrimaryKeyName($class)) &&
            ($insertedId = $result->getInsertedId())
        ) {
            $data->{$pk} = $insertedId;
        }

        return $result;
    }

    /**
     * Performs an insert into model table from a collection of data with column names.
     *
     * @param string $class A class name string.
     * @param iterable $data A collection of model class instances.
     * @param string[] $columns A list of column names to be inserted.
     * @return InsertResult
     */
    final public static function insertMany($class, iterable $data, array $columns)
    {
        static::checkClassImplements($class, ModelInterface::class);

        foreach ($data as $k => $v) {
            if (!$v instanceof $class) {
                throw new InvalidArgumentException(
                    sprintf('Invalid item type in $data[%d], must be of type %s.', $k, $class)
                );
            }
        }

        /** @var ModelDescriptor */
        $modelDescriptor = $class::getModelDescriptor();
        $db = DatabaseManager::connect($modelDescriptor->getDatabase());
        $query = Query::insertInto($modelDescriptor->getTableName(), $data, $columns);
        $result = $db->executeInsert($query);

        if (
            ($pk = static::getSinglePrimaryKeyName($class)) &&
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
     * @param string $class A class name string.
     * @param object $data A model class instance with data to be updated.
     * @param string[] $keys Names of columns that should match to update.
     * @param string[] $columns Column names to be updated.
     * @return UpdateResult
     */
    final public static function update($class, object $data, array $keys, array $columns)
    {
        static::checkClassImplements($class, ModelInterface::class);

        if (!$data instanceof $class) {
            throw new InvalidArgumentException(sprintf('Argument $data must be of type %s.', $class));
        }

        /** @var ModelDescriptor */
        $modelDescriptor = $class::getModelDescriptor();
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
     * @param string $class A class name string.
     * @param object $data A model class instance with data to insert or update.
     * @param string[] $keys Names of columns that should match to update.
     * @param string[] $columns Column names to be updated or inserted.
     * @return UpsertResult
     */
    final public static function upsert($class, object $data, array $keys, array $columns = [])
    {
        static::checkClassImplements($class, ModelInterface::class);

        if (!$data instanceof $class) {
            throw new InvalidArgumentException(sprintf('Argument $data must be of type %s.', $class));
        }

        /** @var ModelDescriptor */
        $modelDescriptor = $class::getModelDescriptor();
        $db = DatabaseManager::connect($modelDescriptor->getDatabase());
        $query = Query::upsert($modelDescriptor->getTableName(), $data, $keys, $columns);
        $result = $db->executeUpsert($query);

        if (
            ($pk = static::getSinglePrimaryKeyName($class)) &&
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
     * @param string $class A class name string.
     * @param iterable $data A collection of model class instances with data to insert or update.
     * @param string[] $keys Names of columns that should match to update.
     * @param string[] $columns Column names to be updated or inserted.
     * @return UpsertResult
     */
    final public static function upsertMany($class, iterable $data, array $keys, array $columns)
    {
        foreach ($data as $k => $v) {
            if (!$v instanceof $class) {
                throw new InvalidArgumentException(
                    sprintf('Invalid item type in $data[%d], must be of type %s.', $k, $class)
                );
            }
        }
        /** @var ModelDescriptor */
        $modelDescriptor = $class::getModelDescriptor();
        $db = DatabaseManager::connect($modelDescriptor->getDatabase());
        $query = Query::upsert($modelDescriptor->getTableName(), $data, $keys, $columns);
        $result = $db->executeUpsert($query);

        if (
            ($pk = static::getSinglePrimaryKeyName($class)) &&
            ($insertedId = $result->getInsertedId())
        ) {
            foreach ($result->getInserts() as $ins) {
                $ins->{$pk} = $insertedId++;
            }
        }

        return $result;
    }

    /**
     * Performs a delete action on all rows that matches the $filter.
     *
     * @param string $class A class name string.
     * @param mixed[] $filter
     * @return DeleteResult
     */
    final public static function delete($class, array $filter)
    {
        /** @var ModelDescriptor */
        $modelDescriptor = $class::getModelDescriptor();
        $db = DatabaseManager::connect($modelDescriptor->getDatabase());
        $query = Query::deleteFrom($modelDescriptor->getTableName(), $filter);
        $result = $db->executeDelete($query);
        return $result;
    }
}
