<?php
declare(strict_types = 1);

namespace Forensic\Handler\Test;

use PHPUnit\Framework\TestCase;
use Forensic\Handler\Validator;
use Forensic\Handler\DateTime;

class ValidatorTest extends TestCase
{
    private $_validator = null;

    public function setUp()
    {
        parent::setUp();
        $this->_validator = new Validator();
    }

    /**
     * test that we can create an instance
    */
    public function testConstruct()
    {
        $validator = new Validator();
        $this->assertInstanceOf(Validator::class, $validator);
    }

    /**
     * test that error bag is set by reference
    */
    public function testErrorBag()
    {
        $error_bag = [
            'first_name' => 'first name field is required',
        ];

        $this->_validator->setErrorBag($error_bag);
        $error_bag['last_name'] = 'last name field is required';

        $this->assertEquals($error_bag, $this->_validator->getErrorBag());
    }
}