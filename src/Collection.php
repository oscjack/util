<?php

namespace Ecpanda\Util;

use Countable;
use ArrayAccess;
use ArrayIterator;
use JsonSerializable;
use IteratorAggregate;
use InvalidArgumentException;
use ArrayableInterface as Arrayable;
use JsonableInterface as Jsonable;

class Collection implements 
    ArrayAccess, Arrayable, Countable, 
    IteratorAggregate, Jsonable, JsonSerializable
{
    /**
     * The items contained in the collection.
     *
     * @var array
     */
    protected $items = [];

    /**
     * Create a new collection.
     *
     * @param  mixed  $items
     * @return void
     */
    public function __construct($items = [])
    {
        $this->items = is_array($items) ? $items : $this->getArrayableItems($items);
    }

    /**
     * Create a new collection instance if the value isn't one already.
     *
     * @param  mixed  $items
     * @return static
     */
    public static function make($items = [])
    {
        return new static($items);
    }

    /**
     * Get all of the items in the collection.
     *
     * @return array
     */
    public function all()
    {
        return $this->items;
    }

    /**
     * Get the average value of a given key.
     *
     * @param  string|null  $key
     * @return mixed
     */
    public function avg($key = null)
    {
        if ($count = $this->count()) {
            return $this->sum($key) / $count;
        }
    }

    /**
     * Get the sum of the given values.
     *
     * @param  callable|string|null  $callback
     * @return mixed
     */
    public function sum($callback = null)
    {
        if (is_null($callback)) {
            return array_sum($this->items);
        }

        $callback = $this->valueRetriever($callback);

        return $this->reduce(function ($result, $item) use ($callback) {
            return $result += $callback($item);
        }, 0);
    }

    /**
     * Collapse the collection of items into a single array.
     *
     * @return static
     */
    public function collapse()
    {
        return new static(Util::collapse($this->items));
    }

    /**
     * Determine if an item exists in the collection.
     *
     * @param  mixed  $key
     * @param  mixed  $value
     * @return bool
     */
    public function contains($key, $value = null)
    {
        if (func_num_args() == 2) {
            return $this->contains(function ($k, $item) use ($key, $value) {
                return Util::getItemByDot($item, $key) == $value;
            });
        }

        if ($this->useAsCallable($key)) {
            return ! is_null($this->first($key));
        }

        return in_array($key, $this->items);
    }

    /**
     * Get the items in the collection that are not present in the given items.
     *
     * @param  mixed  $items
     * @return static
     */
    public function diff($items)
    {
        return new static(
            array_diff($this->items, $this->getArrayableItems($items))
        );
    }

    /**
     * Reduce the collection to a single value.
     *
     * @param  callable  $callback
     * @param  mixed     $initial
     * @return mixed
     */
    public function reduce(callable $callback, $initial = null)
    {
        return array_reduce($this->items, $callback, $initial);
    }

    /**
     * Execute a callback over each item.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function each(callable $callback)
    {
        foreach ($this->items as $key => $item) {
            if ($callback($item, $key) === false) {
                break;
            }
        }

        return $this;
    }

    /**
     * Create a new collection consisting of every n-th element.
     *
     * @param  int  $step
     * @param  int  $offset
     * @return static
     */
    public function every($step, $offset = 0)
    {
        $new = [];

        $position = 0;

        foreach ($this->items as $key => $item) {
            if ($position % $step === $offset) {
                $new[] = $item;
            }

            $position++;
        }

        return new static($new);
    }

    /**
     * Get all items except for those with the specified keys.
     *
     * @param  mixed  $keys
     * @return static
     */
    public function except($keys)
    {
        $keys = is_array($keys) ? $keys : func_get_args();

        return new static(Util::except($this->items, $keys));
    }

    /**
     * Get the collection of items as a plain array.
     *
     * @return array
     */
    public function toArray()
    {
        return array_map(function ($value) {
            return $value instanceof Arrayable ? $value->toArray() : $value;
        }, $this->items);
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * Get the collection of items as JSON.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * Get an iterator for the items.
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->items);
    }

    /**
     * Count the number of items in the collection.
     *
     * @return int
     */
    public function count()
    {
        return count($this->items);
    }

    /**
     * Determine if an item exists at an offset.
     *
     * @param  mixed  $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return array_key_exists($key, $this->items);
    }

    /**
     * Get an item at a given offset.
     *
     * @param  mixed  $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->items[$key];
    }

    /**
     * Set the item at a given offset.
     *
     * @param  mixed  $key
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($key, $value)
    {
        if (is_null($key)) {
            $this->items[] = $value;
        } else {
            $this->items[$key] = $value;
        }
    }

    /**
     * Unset the item at a given offset.
     *
     * @param  string  $key
     * @return void
     */
    public function offsetUnset($key)
    {
        unset($this->items[$key]);
    }

    /**
     * Convert the collection to its string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * Get a value retrieving callback.
     *
     * @param  string  $value
     * @return callable
     */
    protected function valueRetriever($value)
    {
        if ($this->useAsCallable($value)) {
            return $value;
        }

        return function ($item) use ($value) {
            return Util::getItemByDot($item, $value);
        };
    }

    /**
     * Determine if the given value is callable, but not a string.
     *
     * @param  mixed  $value
     * @return bool
     */
    protected function useAsCallable($value)
    {
        return ! is_string($value) && is_callable($value);
    }

    /**
     * Results array of items from Collection or Arrayable.
     *
     * @param  mixed  $items
     * @return array
     */
    protected function getArrayableItems($items)
    {
        if ($items instanceof self) {
            return $items->all();
        } elseif ($items instanceof Arrayable) {
            return $items->toArray();
        } elseif ($items instanceof Jsonable) {
            return json_decode($items->toJson(), true);
        }

        return (array) $items;
    }
}
