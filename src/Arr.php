<?php

namespace Ecpanda\Util;

class Arr
{
    /**
     * Get an item from an array or object using "dot" notation.
     *
     * @param  mixed   $target
     * @param  string|array  $key
     * @param  mixed   $default
     * @return mixed
     */
    public static function getItemByDot($target, $key, $default = null)
    {
        if (is_null($key)) {
            return $target;
        }

        $key = is_array($key) ? $key : explode('.', $key);

        foreach ($key as $segment) {
            if (is_array($target)) {
                if (! array_key_exists($segment, $target)) {
                    return static::value($default);
                }

                $target = $target[$segment];
            } elseif ($target instanceof ArrayAccess) {
                if (! isset($target[$segment])) {
                    return static::value($default);
                }

                $target = $target[$segment];
            } elseif (is_object($target)) {
                if (! isset($target->{$segment})) {
                    return static::value($default);
                }

                $target = $target->{$segment};
            } else {
                return static::value($default);
            }
        }

        return $target;
    }

    /**
     * Return the default value of the given value.
     *
     * @param  mixed  $value
     * @return mixed
     */
    public static function value($value)
    {
        return $value instanceof Closure ? $value() : $value;
    }
    
    /**
     * Collapse an array of arrays into a single array.
     *
     * @param  \ArrayAccess|array  $array
     * @return array
     */
    public static function collapse($array)
    {
        $results = [];

        foreach ($array as $values) {
            if ($values instanceof Collection) {
                $values = $values->all();
            }

            $results = array_merge($results, $values);
        }

        return $results;
    }
}
