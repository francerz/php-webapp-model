<?php

namespace Francerz\WebappModelUtils;

use LogicException;
use Throwable;

class ParamUncheckedException extends LogicException
{
    private $param;

    public function __construct(string $param, $message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->param = $param;
    }

    public function getParam()
    {
        return $this->param;
    }
}
