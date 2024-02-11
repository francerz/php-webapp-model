<?php

namespace Francerz\WebappModelUtils;

use Francerz\SqlBuilder\Components\Table;
use Francerz\SqlBuilder\DatabaseManager;
use Francerz\SqlBuilder\Query;
use Francerz\SqlBuilder\Results\InsertResult;
use Francerz\SqlBuilder\Results\UpdateResult;
use Francerz\SqlBuilder\Results\UpsertResult;
use Francerz\SqlBuilder\SelectQuery;
use InvalidArgumentException;

abstract class AbstractModel
{
    #region Model structure static methods
    /**
     * Returns a database connection parameter object, alias or uri connection string.
     *
     * @return string
     */
    abstract public static function getDatabase(): string;

    /**
     * Returns an string with database table name.
     *
     * @return string
     */
    abstract public static function getTableName(): string;

    /**
     * Returns an sring with a table alias or `null` otherwise.
     * 
     * @return string|null
     */
    public static function getTableAlias(): ?string
    {
        return null;
    }

    /**
     * Returns an array of primary key column names.
     *
     * @return string[]
     */
    abstract public static function getPrimaryKeyNames(): array;

    /**
     * Builds a SelectQuery object based upon given parameters.
     * 
     * This method receives a ModelParams instance to ensure query building consistency.
     * 
     * This method is used by `getQuery()`, `getRows()`, `getFirst` and `getLast()` methods.
     * 
     * @return SelectQuery
     */
    abstract protected static function buildSelectQuery(SelectQuery $query, ModelParams $params): SelectQuery;
    #endregion
 
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
     *  - **@page**: Retreives a chunk of records from result set.
     *  - **@pageSize**: Sets the size of records chunk.
     * 
     * @return SelectQuery
     *
     * @throws UnusedParamsException If any of given parameters weren't properly used in select query building.
     */
    public static final function getQuery(array $params = [])
    {
        // Wraps $params parameter to check select query building consistency.
        $params = new ModelParams($params);

        $tableRef = new Table(static::getTableName(), static::getTableAlias());
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
    public static final function getRows(array $params = [])
    {
        $db = DatabaseManager::connect(static::getDatabase());
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
    public static final function getFirst(array $params = [])
    {
        $rows = static::getRows();
        return reset($rows) ?: null;
    }

    /**
     * Retrieves the last item from a query result set.
     *
     * @param array $params An associative array with select query building parameters.
     * @return static
     */
    public static final function getLast(array $params = [])
    {
        $rows = static::getRows();
        return end($rows) ?: null;
    }

    /**
     * Retrieves the Primary Key name when there's only one. Otherwise returns null.
     *
     * @return string|null Primary key name string.
     */
    private static function getSinglePrimaryKeyName(): ?string
    {
        $pks = static::getPrimaryKeyNames();
        if (count($pks) === 1) {
            return reset($pks);
        }
        return null;
    }

    /**
     * Performs an insert into model table with given data and column names.
     *
     * @param static $data A model class instance with required attribute values.
     * @param string[] $columns A list of column names to be inserted.
     * @return InsertResult
     */
    public static final function insert($data, array $columns)
    {
        if (!$data instanceof static) {
            throw new InvalidArgumentException(sprintf('Argument $data must be of type %s.', static::class));
        }
        $db = DatabaseManager::connect(static::getDatabase());
        $query = Query::insertInto(static::getTableName(), $data, $columns);
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
    public static final function insertMany(iterable $data, array $columns)
    {
        foreach ($data as $k => $v) {
            if (!$v instanceof static) {
                throw new InvalidArgumentException(
                    sprintf('Invalid item type in $data[%d], must be of type %s.', $k, static::class)
                );
            }
        }
        $db = DatabaseManager::connect(static::getDatabase());
        $query = Query::insertInto(static::getTableName(), $data, $columns);
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
    public static final function update($data, array $keys, array $columns)
    {
        if (!$data instanceof static) {
            throw new InvalidArgumentException(sprintf('Argument $data must be of type %s.', static::class));
        }
        $db = DatabaseManager::connect(static::getDatabase());
        $query = Query::update(static::getTableName(), $data, $keys, $columns);
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
    public static final function upsert($data, array $keys, array $columns = [])
    {
        if (!$data instanceof static) {
            throw new InvalidArgumentException(sprintf('Argument $data must be of type %s.', static::class));
        }
        $db = DatabaseManager::connect(static::getDatabase());
        $query = Query::upsert(static::getTableName(), $data, $keys, $columns);
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
    public static final function upsertMany(iterable $data, array $keys, array $columns)
    {
        foreach ($data as $k => $v) {
            if (!$v instanceof static) {
                throw new InvalidArgumentException(
                    sprintf('Invalid item type in $data[%d], must be of type %s.', $k, static::class)
                );
            }
        }
        $db = DatabaseManager::connect(static::getDatabase());
        $query = Query::upsert(static::getTableName(), $data, $keys, $columns);
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
    #endregion

    private $attrChanged = [];
    private $attrValues = [];

    public function __set($name, $value)
    {
        if (isset($this->attrValues[$name]) && $this->attrValues[$name] !== $value) {
            return;
        }
        $this->attrValues[$name] = $value;
        $this->attrChanged[$name] = true;
    }

    public function __get($name)
    {
        if (isset($this->attrValues[$name])) {
            return $this->attrValues[$name];
        }
    }
}
