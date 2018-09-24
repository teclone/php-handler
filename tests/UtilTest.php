<?php
declare(strict_types = 1);

namespace Forensic\Handler\Test;

use Forensic\Handler\Util;
use PHPUnit\Framework\TestCase;

class UtilTest extends TestCase
{
    public function arrayProvider()
    {
        return [
            'array one' => [
                [
                    'name' => 'Harrison',
                    'age' => 22,
                    'features' => array('height' => 4.5, 'complexion' => 'mixed')
                ],
                ['name', 'age'],
                ['features'],
                ['level', 'language']
            ],
        ];
    }

    public function isNumericTestDataProvider()
    {
        return [
            'test 1' => [
                22,
                true
            ],
            'test 2' => [
                '33',
                true
            ],
            'test 3' => [
                'not numberic',
                false
            ],
            'test 4' => [
                '+0.4',
                true
            ],
        ];
    }

    /**
     *@dataProvider arrayProvider
     *
    */
    public function testValue($arr, $keys, $arr_keys, $invalid_keys)
    {
        foreach($keys as $key)
            $this->assertEquals($arr[$key], Util::value($key, $arr));

        foreach($invalid_keys as $key)
            $this->assertNull(Util::value($key, $arr));

        $default = 'default';

        foreach($invalid_keys as $key)
            $this->assertEquals($default, Util::value($key, $arr, $default));
    }

    /**
     *@dataProvider arrayProvider
     *
    */
    public function testArrayValue($arr, $keys, $arr_keys, $invalid_keys)
    {
        foreach($keys as $key)
            $this->assertEquals([], Util::arrayValue($key, $arr));

        foreach($invalid_keys as $key)
            $this->assertEquals([], Util::arrayValue($key, $arr));

        foreach($arr_keys as $key)
            $this->assertEquals($arr[$key], Util::arrayValue($key, $arr));

        $default = ['name' => 'Harrison'];

        foreach($invalid_keys as $key)
            $this->assertEquals($default, Util::arrayValue($key, $arr, $default));
    }

    /**
     * test that it returns true if key is not in array, or if key value is truthy, otherwise,
     * it should return false
    */
    public function testKeyNotSetOrTrue()
    {
        $arr = array('required' => true, 'filter' => false);

        $this->assertTrue(Util::keyNotSetOrTrue('required', $arr));
        $this->assertTrue(Util::keyNotSetOrTrue('unknown', $arr));

        $this->assertFalse(Util::keyNotSetOrTrue('filter', $arr));
    }

    /**
     * test that it returns true if key is in array and its value is truthy
     * otherwise, it should return false
    */
    public function testKeySetAndTrue()
    {
        $arr = array('required' => true, 'filter' => false);

        $this->assertTrue(Util::keySetAndTrue('required', $arr));

        $this->assertFalse(Util::keySetAndTrue('filter', $arr));

        $this->assertFalse(Util::keySetAndTrue('unknown', $arr));
    }

    /**
     * test that it returns true if key is not in array, or if key value is truthy, otherwise,
     * it should return false
     *
     *@dataProvider isNumericTestDataProvider
    */
    public function testIsNumeric($value, $expected)
    {
        $this->assertEquals($expected, Util::isNumeric($value));
    }

    /**
     * test that it correctly unsets the given key from the array
     *
     *@dataProvider arrayProvider
    */
    public function testUnsetFromArray($arr, $keys, $arr_keys)
    {
        foreach($keys as $key)
        {
            $this->assertTrue(array_key_exists($key, $arr));
            Util::unsetFromArray($key, $arr);
            $this->assertFalse(array_key_exists($key, $arr));
        }
    }

    /**
     * test that it correctly turns a non array value into an array, and returns an array
     * argument untouched
    */
    public function testMakeArray()
    {
        $this->assertEquals([22], Util::makeArray(22));
        $this->assertEquals([22], Util::makeArray([22]));
    }
}