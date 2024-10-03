<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

namespace BTiPay\Factory;

use PrestaShop\PrestaShop\Adapter\ServiceLocator;

if (!defined('_PS_VERSION_')) {
    exit;
}

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
