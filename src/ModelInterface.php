<?php

namespace Francerz\WebappModelUtils;

use Francerz\SqlBuilder\SelectQuery;

interface ModelInterface
{
    /**
     * Returns a database connection parameter object, alias or uri connection string.
     *
     * @return string
     */
    public static function getDatabase(): string;

    /**
     * Returns an string with database table name.
     *
     * @return string
     */
    public static function getTableName(): string;

    /**
     * Returns an sring with a table alias or `null` otherwise.
     *
     * @return string|null
     */
    public static function getTableAlias(): ?string;

    /**
     * Returns an array of primary key column names.
     *
     * @return string[]
     */
    public static function getPrimaryKeyNames(): array;

    /**
     * Builds a SelectQuery object based upon given parameters.
     *
     * This method receives a ModelParams instance to ensure query building consistency.
     *
     * This method is used by `getQuery()`, `getRows()`, `getFirst()` and `getLast()` methods.
     *
     * @return SelectQuery
     */
    public static function buildSelectQuery(SelectQuery $query, ModelParams $params): SelectQuery;
}
