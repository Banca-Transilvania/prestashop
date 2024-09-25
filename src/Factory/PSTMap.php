<?php
namespace BTiPay\Factory;

use PrestaShop\PrestaShop\Adapter\ServiceLocator;

class PSTMap implements \IteratorAggregate, \Countable, \ArrayAccess
{
    private $type;
    private $items = [];
    private $instances = [];

    public function __construct($type, array $items = [])
    {
        $this->type = $type;
        $this->items = $items;
    }

    public function getIterator()
    {
        foreach ($this->items as $key => $value) {
            yield $key => $this->offsetGet($key);
        }
    }

    public function count()
    {
        return count($this->items);
    }

    public function offsetSet($offset, $value)
    {
        if ($offset === null) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetExists($offset)
    {
        return isset($this->items[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->items[$offset], $this->instances[$offset]);
    }

    public function offsetGet($offset)
    {
        if (!isset($this->instances[$offset])) {
            $this->instances[$offset] = $this->createObject($this->items[$offset]);
        }
        return $this->instances[$offset];
    }

    private function createObject($className)
    {
        if (is_object($className)) {
            return $className;
        }

        if (!class_exists($className)) {
            throw new \InvalidArgumentException("Class $className does not exist");
        }
        return ServiceLocator::get($className);
    }
}
