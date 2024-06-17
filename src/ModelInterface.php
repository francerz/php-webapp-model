<?php

namespace Francerz\WebappModelUtils;

use Francerz\SqlBuilder\SelectQuery;

/**
 * @deprecated
 */
interface ModelInterface
{
    /**
     * Model Descriptor
     *
     * @return ModelDescriptor
     */
    public static function getModelDescriptor(): ModelDescriptor;

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
