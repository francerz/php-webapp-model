<?php

namespace Francerz\WebappModelUtils\Tests;

use Francerz\WebappModelUtils\Dev\Models\FirstOne;
use Francerz\WebappModelUtils\Dev\Models\SecondTwo;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class AbstractModelTest extends TestCase
{
    private static function getPrivateStaticMethod(string $class, string $method)
    {
        $classRef = new ReflectionClass($class);
        $methodRef = $classRef->getMethod($method);
        $methodRef->setAccessible(true);
        return $methodRef;
    }

    public function testGetModelDescriptorCahed()
    {
        $md1 = static::getPrivateStaticMethod(FirstOne::class, 'getModelDescriptorCached')->invoke(null);
        $this->assertEquals('db1', $md1->getDatabase());
        $this->assertEquals('first_one', $md1->getTableName());
        $this->assertEquals('fo', $md1->getTableAlias());

        $md2 = static::getPrivateStaticMethod(SecondTwo::class, 'getModelDescriptorCached')->invoke(null);
        $this->assertEquals('db2', $md2->getDatabase());
        $this->assertEquals('second_two', $md2->getTableName());
        $this->assertEquals('st', $md2->getTableAlias());
    }

    public function testGetQuery()
    {
        $query1 = FirstOne::getQuery();
        $this->assertEquals('first_one', $query1->getTable()->getSource());
        $this->assertEquals('fo', $query1->getTable()->getAlias());

        $query2 = SecondTwo::getQuery();
        $this->assertEquals('second_two', $query2->getTable()->getSource());
        $this->assertEquals('st', $query2->getTable()->getAlias());
    }
}
