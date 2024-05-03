<?php

namespace Cartrabbit\Request;

class Helper
{
    public static function dataGet(array $array, string $key, $default = null)
    {
        $keys = explode('.', $key);

        foreach ($keys as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }

            $array = $array[$segment];
        }

        return $array;
    }
}