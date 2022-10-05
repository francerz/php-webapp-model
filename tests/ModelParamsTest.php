<?php

namespace Francerz\WebappModelUtils\Tests;

use Francerz\WebappModelUtils\ModelParams;
use Francerz\WebappModelUtils\ParamUncheckedException;
use Francerz\WebappModelUtils\UnusedParamsException;
use PHPUnit\Framework\TestCase;

class ModelParamsTest extends TestCase
{
    public function testCheck()
    {
        $params = new ModelParams([
            'alpha' => 1,
            'bravo' => 'abc',
            'charlie' => [1, 2, 3]
        ]);

        $this->assertFalse(isset($params[null]));
        $this->assertFalse(isset($params['']));
        $this->assertFalse(isset($params[0]));
        $this->assertFalse(isset($params['delta']));
        $this->assertTrue(isset($params['alpha']));
        $this->assertTrue(isset($params['bravo']));
        $this->assertTrue(isset($params['charlie']));

        $this->assertEquals(1, $params['alpha']);
        $this->assertEquals('abc', $params['bravo']);
        $this->assertEquals([1, 2, 3], $params['charlie']);
        $this->assertEquals([], $params->getSubparams('alpha'));
        $this->assertEquals([], $params->getSubparams('bravo'));
        $this->assertEquals([1, 2, 3], $params->getSubparams('charlie'));
    }

    public function testParamUncheckedException()
    {
        $params = new ModelParams(['alpha' => 1, 'bravo' => 2]);

        $this->expectException(ParamUncheckedException::class);
        $this->assertEquals(1, $params['alpha']);
    }

    public function testUnusedParamsException()
    {
        $params = new ModelParams(['alpha' => 1, 'bravo' => 2]);
        $this->assertTrue(isset($params['alpha']));
        $this->assertEquals(1, $params['alpha']);

        $this->expectException(UnusedParamsException::class);
        $params->checkUsed();
    }
}
