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
}