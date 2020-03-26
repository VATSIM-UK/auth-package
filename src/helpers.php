<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;


if (!function_exists('data_has')) {
    /**
     * Get an item from an array or object using "dot" notation.
     *
     * @param mixed $target
     * @param string|array|int $key
     * @return mixed
     */
    function data_has($target, $key)
    {
        if (is_null($key)) {
            return true;
        }

        $key = is_array($key) ? $key : explode('.', $key);

        while (!is_null($segment = array_shift($key))) {
            if ($segment === '*') {
                if ($target instanceof Collection) {
                    $target = $target->all();
                } elseif (!is_array($target)) {
                    return false;
                }

                $result = [];

                foreach ($target as $item) {
                    $result[] = data_get($item, $key);
                }

                return true;
            }

            if (Arr::accessible($target) && Arr::exists($target, $segment)) {
                $target = $target[$segment];
            } elseif (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            } else {
                return false;
            }
        }

        return true;
    }
}


if (!function_exists('array_to_dot')) {
    /**
     * Extends the Laravel Arr::dot() function to allow for schema arrays rather than arrays with associated values
     *
     * @param $array
     * @return Collection
     */
    function array_to_dot($array): Collection
    {
        $dotted = collect(Arr::dot($array));

        return $dotted->map(function ($value, $key) {
            $exploded = explode('.', $key);
            array_pop($exploded);
            $exploded[] = $value;
            $newValue = implode(".", $exploded);

            return $newValue;
        })->sort()->values();

    }
}

if (!function_exists('arrayToObject')) {
    /**
     * @param ?array $array
     * @return stdClass
     */
    function arrayToObject($array)
    {
        // First we convert the array to a json string
        $json = json_encode($array);

        // The we convert the json string to a stdClass()
        $object = json_decode($json);

        return $object;
    }
}