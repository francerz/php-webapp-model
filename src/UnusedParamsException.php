<?php

namespace Francerz\WebappModelUtils;

use LogicException;

class UnusedParamsException extends LogicException
{
    private $params;

    /**
     * @param string[] $params
     * @param string $message
     * @param integer $code
     * @param \Throwable|null $previous
     */
    public function __construct(array $params, $message = "", $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->params = $params;
    }

    public function getParams()
    {
        return $this->params;
    }
}
