<?php
declare(strict_types = 1);

namespace Forensic\Handler\Test;

use Forensic\Handler\Handler;
use PHPUnit\Framework\TestCase;
use Forensic\Handler\Exceptions\DataSourceNotRecognizedException;
use Forensic\Handler\Exceptions\DataNotFoundException;
use Forensic\Handler\Exceptions\RuleNotFoundException;

class HandlerTest extends TestCase
{

    /**
     * test that we can create an instance without any argument
    */
    public function testCreateInstanceWithNoArgument()
    {
        $instance = new Handler();
        $this->assertInstanceOf(Handler::class, $instance);
    }

    /**
     * test setting the data source using a string pointer during construction
    */
    public function testCreateInstanceWithStringSourcePointer()
    {
        $instance = new Handler('get');
        $this->assertInstanceOf(Handler::class, $instance);

        $instance = new Handler('post');
        $this->assertInstanceOf(Handler::class, $instance);
    }

    /**
     * test setting the data source to array during construction
    */
    public function testCreateInstanceWithArraySource()
    {
        $instance = new Handler([]);
        $this->assertInstanceOf(Handler::class, $instance);
    }

    /**
     * test setting the data source using an unrecognized string source pointer
    */
    public function testCreateInstanceWithUnrecognizedDataSource()
    {
        $this->expectException(DataSourceNotRecognizedException::class);
        $instance = new Handler('');

        $this->expectException(DataSourceNotRecognizedException::class);
        $instance = new Handler('put');
    }
}