<?php

namespace Core\Concerns;

use Core\Model;

class Collection implements \ArrayAccess, \Iterator, \Countable, \JsonSerializable
 
{
    private $position = 0;
    private $items = [];
    public $execute;
    protected $model = null;

    public function info() {
        return $this->execute;
    }
    public function __construct($items = [])
    {
        if($items instanceof Collection) {
            $this->items = $items->toArray();
        } else {
            $this->items = $items;
        }
    }
    public function offsetSet($offset, $value): void {
        if (is_null($offset)) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetExists($offset): bool {
        return isset($this->items[$offset]);
    }

    public function offsetUnset($offset):void {
        unset($this->items[$offset]);
    }
    #[\ReturnTypeWillChange]
    public function offsetGet($offset) {
        return isset($this->items[$offset]) ? $this->items[$offset] : null;
    }
    public function rewind(): void {
        $this->position = 0;
    }
    #[\ReturnTypeWillChange]
    public function current() {
        return $this->items[$this->position];
    }
    #[\ReturnTypeWillChange]
    public function key() {
        return $this->position;
    }
    public function next(): void {
        ++$this->position;
		}
		public function has() {
			return !empty($this->items);
		}
    public function valid(): bool {
        return isset($this->items[$this->position]);
		}
		public function pluck($value, $key = null) {
			$rp = [];
			$ii = $this->toArray();
			if($key === null) {
				return array_map(function($n) use($value) {
					return $n->{$value};
				}, $ii);
			}
			foreach($ii as $n) {
				//dd([$key, $value, $n->{$key}, $n->{$value}]);
			  $rp[$n->{$key}] = $n->{$value};
			}
			return $rp;
    }
    public function map(callable $callback)
    {
        $this->items = array_map(function($n) use($callback) {
            return $callback((object) $n);
        }, $this->items);
        return $this;
    }
    public static function range($from, $to)
    {
        return new static(range($from, $to));
    }
    public function add($item)
    {
        $this->items[] = $item;

        return $this;
    }
    public function keys()
    {
        return new static(array_keys($this->items));
    }
    public function take($limit)
    {
        if ($limit < 0) {
            return $this->slice($limit, abs($limit));
        }

        return $this->slice(0, $limit);
    }
    public function slice($offset, $length = null)
    {
        return new static(array_slice($this->items, $offset, $length, true));
    }
    public function count(): int
    {
        return count($this->items);
    }
    public function values()
    {
        return new static(array_values($this->items));
    }
    public function all()
    {
        return $this->items;
        return array_map(function($n) { return (object) $n; }, $this->items);
    }
    public function hydrate($model) {
        $this->model = $model;
        $this->items = array_map(function($n) use($model) { return new $model((array) $n); }, $this->items);
        return $this;
    }
    public function sort($callback) {
        usort($this->items, $callback);
        return $this;
    }
    public function first() {
        foreach($this->items as $e) {
            return (object) $e;
        }
    }
    public function implode($value, $glue = null)
    {
        return implode($glue ?? '', $this->map($value)->all());
    }
    public function isEmpty()
    {
        return empty($this->items);
    }
    public function __toArray() {
        return $this->toArray();
    }
    public function toArray() {
        return (array) $this->items;
    }
    public function __toString() {
        return sprintf('====%s', __CLASS__);
    }
    public function jsonSerialize() : array {
        return (array) $this->items;
    }
}
