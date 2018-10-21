<?php
/**
 * provides utility methods
*/
declare(strict_types = 1);

namespace Forensic\Handler;

class Util
{
    /**
     * returns the value for the given array key if it exists, otherwise, return the default
     * value
     *
     *@param string $key - the array key
     *@param array $arr - the array
     *@param mixed [$default=null] - the default value to return if otherwise
     *@return mixed
    */
    public static function value(string $key, array $arr, $default = null)
    {
        if (array_key_exists($key, $arr))
            return $arr[$key];
        else
            return $default;
    }

    /**
     * returns array value for the given array key if key exists and its value is an array
     * otherwise, return the default value
     *
     *@param string $key - the array key
     *@param array $arr - the array
     *@param array [$default=[]] - the default value to return if otherwise
     *@return array
    */
    public static function arrayValue(string $key, array $arr, array $default = [])
    {
        if (array_key_exists($key, $arr) && is_array($arr[$key]))
            return $arr[$key];
        else
            return $default;
    }

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
     * unsets an entry from an array if the key exists
     *
     *@param string $key - the array key
     *@param array $arr - the array
    */
    public static function unsetFromArray(string $key, array &$arr)
    {
        if (array_key_exists($key, $arr))
            unset($arr[$key]);
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
}