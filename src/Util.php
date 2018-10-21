<?php
/**
 * provides utility methods
*/
declare(strict_types = 1);

namespace Forensic\Handler;

class Util
{
    /**
     * returns true if the given key is not set or if the key is set and it is truthy
     *
     *@param string $key - the array key
     *@param array $arr - the array
     *@return bool
    */
    public static function keyNotSetOrTrue(string $key, array $arr)
    {
        if (!array_key_exists($key, $arr) || $arr[$key])
            return true;
        else
            return false;
    }

    /**
     * returns true if the given key is set and it is truthy
     *
     *@param string $key - the array key
     *@param array $arr - the array
     *@return bool
    */
    public static function keySetAndTrue(string $key, array $arr)
    {
        if (array_key_exists($key, $arr) && $arr[$key])
            return true;
        else
            return false;
    }

    /**
     * returns boolean value indicating if value is numberic
     *
     *@param mixed $value - the value
    */
    public static function isNumeric($value)
    {
        $value = strval($value);
        if (preg_match('/^[-+.]?\d+/', $value))
            return true;
        else
            return false;
    }

    /**
     * puts the value inside an array and returns the resulting array or returns the value if
     * it is already an array
     *
     *@param mixed $value - the value
     *@return array
    */
    public static function makeArray($value)
    {
        if(is_array($value))
            return $value;
        else
            return array($value);
    }

    /**
     * returns the value for the first key in the keys array that exists in the array
     * otherwise, return the default value
     *
     *@param string[]|string $keys - array of keys or a single string key
     *@param array $arr - the array
     *@param mixed [$default=null] - the default value to return if otherwise
     *@return mixed
    */
    public static function value($keys, array $arr, $default = null)
    {
        $keys = self::makeArray($keys);
        foreach($keys as $key)
        {
            if (array_key_exists($key, $arr))
                return $arr[$key];
        }

        return $default;
    }

    /**
     * returns the value for the first key in the keys array whose value is an array that
     * exists in the array
     * otherwise, return the default value
     *
     *@param string[]|string $keys - array of keys or a single string key
     *@param array $arr - the array
     *@param mixed [$default=[]] - the default value to return if otherwise
     *@return array
    */
    public static function arrayValue($keys, array $arr, array $default = [])
    {
        $keys = self::makeArray($keys);
        foreach($keys as $key)
        {
            if (array_key_exists($key, $arr) && is_array($arr[$key]))
                return $arr[$key];
        }

        return $default;
    }

    /**
     * unsets array of keys from the given array if it exists in the array
     *
     *@param string[]|string $keys - array of keys or a single string key
     *@param array $arr - the array
    */
    public static function unsetFromArray($keys, array &$arr)
    {
        $keys = self::makeArray($keys);
        foreach($keys as $key)
        {
            if (array_key_exists($key, $arr))
                unset($arr[$key]);
        }
    }
}