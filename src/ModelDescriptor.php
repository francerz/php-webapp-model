<?php

namespace Francerz\WebappModelUtils;

class ModelDescriptor
{
    private $database;
    private $tableName;
    private $tableAlias;
    private $primaryKeyNames = [];

    public function __construct(string $database, string $tableName, ?string $tableAlias = null)
    {
        $this->database = $database;
        $this->tableName = $tableName;
        $this->tableAlias = $tableAlias;
    }

    /**
     * @param string[] $primaryKeyNames
     * @return static
     */
    public function withPrimaryKeyNames(array $primaryKeyNames)
    {
        $clone = clone $this;
        $clone->primaryKeyNames = $primaryKeyNames;
        return $clone;
    }

    /**
     * @param string[] $primaryKeyNames
     * @return void
     */
    public function setPrimaryKeyNames(array $primaryKeyNames)
    {
        $this->primaryKeyNames = $primaryKeyNames;
    }

    /**
     * Returns a database connection alias, or connection string.
     *
     * @return string
     */
    public function getDatabase(): string
    {
        return $this->database;
    }

    /**
     * Returns an string with database table name.
     *
     * @return string
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * Returns an string with table alias or `null` otherwise.
     *
     * @return string|null
     */
    public function getTableAlias(): ?string
    {
        return $this->tableAlias;
    }

    /**
     * Returns an array of primary key column names.
     *
     * @return string[]
     */
    public function getPrimaryKeyNames(): array
    {
        return $this->primaryKeyNames;
    }
}
