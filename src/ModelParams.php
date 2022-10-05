<?php

namespace Francerz\WebappModelUtils;

use ArrayAccess;
use Countable;
use Iterator;

class ModelParams implements ArrayAccess, Countable, Iterator
{
    private $params;
    private $offsetGet = [];
    private $offsetExists = [];

    public function __construct(array $params)
    {
        $this->params = $params;
    }

    /**
     * @param mixed $offset The offset to assign the value to.
     * @param mixed $value The value to set.
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->params[$offset] = $value;
    }

    /**
     * @param mixed $offset An offset to check for
     * @return boolean
     */
    public function offsetExists($offset)
    {
        $this->offsetExists[$offset] = true;
        return array_key_exists($offset, $this->params);
    }

    /**
     * @param mixed $offset The offset to retrieve.
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset)
    {
        if (!isset($this->offsetExists[$offset])) {
            throw new ParamUncheckedException(
                $offset,
                "Retrieving `\$params['{$offset}']` without `isset(\$params['{$offset}'])`.",
                0,
                null
            );
        }
        $this->offsetGet[$offset] = true;
        return $this->params[$offset] ?? null;
    }

    /**
     * @param mixed $offset — The offset to unset.
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($params[$offset]);
    }

    public function count()
    {
        return count($this->params);
    }

    public function key()
    {
        return key($this->params);
    }

    public function valid()
    {
        return key($this->params) !== null;
    }

    public function current()
    {
        return current($this->params);
    }

    public function next()
    {
        next($this->params);
    }

    public function rewind()
    {
        reset($this->params);
    }

    /**
     * Retrieves an array of specific key, only if given key's value is an array.
     * returns an empty array otherwise.
     *
     * @param mixed $offset
     * @return array
     */
    public function getSubparams($offset): array
    {
        $value = $this[$offset];
        return is_array($value) ? $value : [];
    }

    /**
     * Retrieves all unused params.
     *
     * @return string[]
     */
    private function getUnusedParams()
    {
        $usedKeys = array_keys($this->offsetGet ?? []);
        $paramsKeys = array_keys($this->params);
        return array_diff($paramsKeys, $usedKeys);
    }

    /**
     * Checks that all given params were used.
     *
     * @return boolean Returns true if all given params were used.
     *
     * @throws UnusedParamsException if any parameter weren't used.
     */
    public function checkUsed()
    {
        $unusedParams = $this->getUnusedParams();
        if (count($unusedParams) === 0) {
            return true;
        }
        $missedParams = array_unique($unusedParams);
        $message = 'Unused params: ' . join(', ', $missedParams);
        throw new UnusedParamsException($missedParams, $message);
    }
}
