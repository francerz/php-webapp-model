<?php

namespace Francerz\WebappModelUtils\Dev\Models;

use Francerz\SqlBuilder\SelectQuery;
use Francerz\WebappModelUtils\AbstractModel;
use Francerz\WebappModelUtils\ModelDescriptor;
use Francerz\WebappModelUtils\ModelParams;

class SecondTwo extends AbstractModel
{
    public static function getModelDescriptor(): ModelDescriptor
    {
        return new ModelDescriptor('db2', 'second_two', 'st');
    }

    public static function buildSelectQuery(SelectQuery $query, ModelParams $params): SelectQuery
    {
        return $query;
    }
}
