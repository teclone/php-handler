<?php
declare(strict_types = 1);

namespace Forensic\Handler\Test;

use PHPUnit\Framework\TestCase;
use Forensic\Handler\Validator;
use Forensic\Handler\DateTime;

class ValidatorTest extends TestCase
{
    private $_validator = null;

    /**
     * provides data used for validating type rules
    */
    public function typeRulesTestDataProvider()
    {
        return [
            //date validation data
            'date correct set' => [
                'validateDate',
                'date-of-birth',
                ['2018-01-04', '20180104', "2018\t01\t04"],
            ],
            'date wrong set' => [
                'validateDate',
                'date-of-birth',
                ['01-04-2018', '2018-01-32'],
                [],
                [
                    '01-04-2018 is not a valid date format',
                    '2018-01-32 is not a valid date',
                ],
            ],

            //integer validation data
            'correct integer set' => [
                'validateInteger',
                'product-number',
                ['12'],
            ],
            'wrong integer set' => [
                'validateInteger',
                'product-number',
                ['a22'],
                [],
                [
                    '"a22" is not a valid integer',
                ],
            ],

            //negative integer validation
            'correct negative integer set' => [
                'validateNInteger',
                'product-number',
                ['-12'],
            ],
            'wrong negative integer set' => [
                'validateNInteger',
                'product-number',
                ['22',],
                [],
                ['22 is not a valid negative integer'],
            ],

            //positive integer validation
            'correct positive integer set' => [
                'validatePInteger',
                'product-number',
                ['12'],
            ],
            'wrong positive integer set' => [
                'validatePInteger',
                'product-number',
                ['-22',],
                [],
                ['-22 is not a valid positive integer'],
            ],

            //float validation
            'correct float set' => [
                'validateFloat',
                'product-number',
                ['12.22', 0.22, '0.2333aaa'],
            ],
            'wrong float set' => [
                'validateFloat',
                'product-number',
                ['-aaa222',],
                [],
                ['"-aaa222" is not a valid number'],
            ],

            //positive float validation
            'correct positive float set' => [
                'validatePFloat',
                'product-number',
                ['12.22', 0.22, '0.2333'],
            ],
            'wrong positive float set' => [
                'validatePFloat',
                'product-number',
                ['-0.222',],
                [],
                ['-0.222 is not a valid positive number'],
            ],

            //negative float validation
            'correct negative float set' => [
                'validateNFloat',
                'product-number',
                ['-12.22', -0.22, '-0.2333'],
            ],
            'wrong negative float set' => [
                'validateNFloat',
                'product-number',
                ['0.222',],
                [],
                ['0.222 is not a valid negative number'],
            ],
        ];
    }

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

    /**
     * test that required fields are validated accordingly
    */
    public function testRequiredFields()
    {
        $this->_validator->validateText(true, 'first_name', null, []);
        $this->assertTrue($this->_validator->fails());
        $this->assertEquals('first_name field is required',
            $this->_validator->getErrorBag()['first_name']);
    }

    /**
     * runs test that checks on a specific rule
    */
    public function validationRulesTester(string $method, string $field, array $values,
        array $options = [], array $errs = [], bool $required = true)
    {
        $len = count($values);
        if ($len === 0)
            return;

        $is_errornous = count($errs) > 0? true : false;

        while(--$len >= 0)
        {
            $value = $values[$len];
            $this->_validator->{$method}($required, $field, $value, $options);

            if ($is_errornous)
            {
                $this->assertTrue($this->_validator->fails());
                $error_bag = $this->_validator->getErrorBag();

                $this->assertArrayHasKey($field, $error_bag);
                $this->assertEquals($errs[$len], $error_bag[$field]);
            }
            else
            {
                $this->assertTrue($this->_validator->succeeds());
            }
        }
    }
}