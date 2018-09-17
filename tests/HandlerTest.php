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
     * returns test data
     *
     *@return array
    */
    public function getSimpleData()
    {
        return [
            'first-name' => 'Harrison',
            'last-name' => 'Ifeanyichukwu',
            'age' => 22,
            'password1' => 'random',
            'password2' => '',
            'timestamp' => '',
            'date-of-birth' => '',
        ];
    }

    /**
     * returns test rules
     *
     *@return array
    */
    public function getSimpleRules()
    {
        return [
            'first-name' => [
                'type' => 'text',
                'hint' => 'first-name field is required',
                'options' => [
                    'min' => 3,
                    'max' => 15,
                ],
            ],
            'last-name' => [
                'type' => 'text'
            ],
            'middle-name' => [
                'required' => false
            ],
            'age' => [
                'type' => 'int',
                'required' => false
            ],
            'password1' => [
                'type' => 'text'
            ],
            'password2' => [
                'required' => false,
                'default' => '{password1}'
            ],
            'timestamp' => [
                'required' => false,
                'default' => '{CURRENT_TIME}'
            ],
            'date-of-birth' => [
                'required' => false,
                'default' => '{CURRENT_DATE}'
            ],
        ];
    }

    /**
     * provides data and rules with some fields missing
     *
     *@return array
    */
    public function missingFieldsTestDataProvider()
    {
        return [
            'first set' => [
                //data section
                [
                    'first_name' => 'Harrison',
                    'languages' => array(),
                    'hobbies' => array('programming', 'teaching', 'footballing')
                ],
                //rules section
                [
                    'first_name' => [
                        'type' => 'text',
                        'hint' => '{_this} is required'
                    ],
                    'last_name' => [
                        'type' => 'text',
                        'hint' => '{_this} is required'
                    ],
                    'languages' => [
                        'type' => 'text',
                        'hint' => '{_this} field is required'
                    ],
                    'hobbies' => [
                        'type' => 'text',
                    ],
                ],
                // is erronous
                true,
                //missing fields section
                [
                    'last_name' => 'last_name is required',
                    'languages' => 'languages field is required',
                ]
            ],
        ];
    }

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

    /**
     * test create instance with rule
    */
    public function testCreateInstanceWithRules()
    {
        $instance = new Handler(null, $this->getSimpleRules());
        $this->assertInstanceOf(Handler::class, $instance);
    }

    /**
     * test that it throws error if executed with no data set
    */
    public function testExecuteWithNoDataSet()
    {
        $this->expectException(DataNotFoundException::class);
        $instance = new Handler();
        $instance->execute();
    }

    /**
     * test that it throws error if executed with no rule set
    */
    public function testExecuteWithNoRulesSet()
    {
        $this->expectException(RuleNotFoundException::class);
        $instance = new Handler($this->getSimpleData());
        $instance->execute();
    }

    /**
     * test that it runs the execution with no errors if both data and rules are set
    */
    public function testExecuteWithDataAndRulesSet()
    {
        $instance = new Handler($this->getSimpleData(), $this->getSimpleRules());
        $this->assertTrue($instance->fails());

        $instance->execute();
        $this->assertTrue($instance->succeeds());

        //test that calling the execute method multiple times has no side effect
        $instance->execute();
        $this->assertTrue($instance->succeeds());
    }

    /**
     * tests for a specific handler feature
    */
    public function executeHandlerFeature(array $data, array $rules, bool $erroneous, array $errors)
    {
        $instance = new Handler($data, $rules);
        $instance->execute();

        if ($erroneous)
        {
            $this->assertTrue($instance->fails());
            foreach ($errors as $key => $err) {
                $this->assertEquals($err, $instance->getError($key));
            }
        }
        else
        {
            $this->assertTrue($instance->succeeds());
            foreach ($errors as $key => $err) {
                $this->assertEquals($err, $instance->getData($key));
            }
        }
    }

    /**
     *@dataProvider missingFieldsTestDataProvider
    */
    public function testMissingFields(...$args)
    {
        $this->executeHandlerFeature(...$args);
    }
}