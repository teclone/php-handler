<?php
declare(strict_types = 1);

namespace Forensic\Handler\Test\Unit;

use Forensic\Handler\Handler;
use PHPUnit\Framework\TestCase;
use Forensic\Handler\Exceptions\DataSourceNotRecognizedException;
use Forensic\Handler\Exceptions\DataSourceNotSetException;
use Forensic\Handler\Exceptions\RulesNotSetException;
use Forensic\Handler\Exceptions\DataNotFoundException;
use PHPUnit\Framework\Error\Warning;

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
            'fav-negative-int' => '-10',
            'fav-positive-int' => '4',
            'height' => '5.4ft',
            'fav-negative-float' => '-10.00',
            'fav-positive-float' => '4.00',
            'password1' => 'random',
            'password2' => '',
            'timestamp' => '',
            'date-of-birth' => '',
            'email' => 'Harrisonifeanyichukwu@gmail.com',
            'website' => 'www.example.com',
            'terms-and-condition' => 'no'
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
            'fav-negative-int' => [
                'type' => 'negativeInteger',
            ],
            'fav-positive-int' => [
                'type' => 'pInt',
            ],
            'height' => [
                'type' => 'float',
            ],
            'fav-negative-float' => [
                'type' => 'nFloat',
            ],
            'fav-positive-float' => [
                'type' => 'pNumber',
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
                'type' => 'date',
                'default' => '{CURRENT_DATE}'
            ],
            'email' => [
                'type' => 'email',
            ],
            'website' => [
                'type' => 'url',
            ],
            'terms-and-condition' => [
                'type' => 'boolean',
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
     *
     *@return array
    */
    public function filterTestDataProvider()
    {
        return [
            'first set' => [
                //data
                [
                    'ages' => array('22yrs', '22.5years'),
                    'last-name' => '',
                    'remember-me' => 'off',
                    'terms-and-conditions' => 'on',
                    'fav-numbers' => array('4', '7', '10', '11'),
                    'height' => '5.4ft',
                    'email' => '(Harrisonifeanyichukwu@gmail.com)',
                    'website' => 'http://www.fjsfoundations.com',
                    'alpha-one' => 'a',
                    'alpha-two' => 'Z'
                ],
                //rules
                [
                    'ages' => [
                        'type' => 'positiveFloat',
                    ],
                    'last-name' => [
                        'required' => false,
                    ],
                    'remember-me' => [
                        'type' => 'boolean'
                    ],
                    'terms-and-conditions' => [
                        'type' => 'boolean'
                    ],
                    'fav-numbers' => [
                        'type' => 'int'
                    ],
                    'height' => [
                        'type' => 'float'
                    ],
                    'email' => [
                        'type' => 'email'
                    ],
                    'website' => [
                        'type' => 'url'
                    ],
                    'alpha-one' => [
                        'filters' => [
                            'toUpper' => true
                        ],
                    ],
                    'alpha-two' => [
                        'filters' => [
                            'toLower' => true
                        ],
                    ],
                ],
                //is erronous
                false,
                //expected
                [
                    'ages' => array(22, 22.5),
                    'last-name' => null,
                    'remember-me' => false,
                    'terms-and-conditions' => true,
                    'fav-numbers' => array(4, 7, 10, 11),
                    'height' => '5.4',
                    'email' => 'Harrisonifeanyichukwu@gmail.com',
                    'website' => 'http://www.fjsfoundations.com',
                    'alpha-one' => 'A',
                    'alpha-two' => 'z'
                ]
            ],
        ];
    }

    /**
     * return test data used for testing requireIf conditions
    */
    public function requireIfTestDataProvider()
    {
        return [
            //test not checked condition
            'first set' => [
                //data
                [
                    'is-current-work' => 'on',
                    'work-end-month' => '',
                    'work-end-year' => '',
                ],
                //rules
                [
                    'is-current-work' => [
                        'type' => 'boolean',
                    ],
                    'work-end-month' => [
                        'type' => 'range',
                        'options' => [
                            'from' => 1,
                            'to' => 12
                        ],
                        'requireIf' => [
                            'condition' => 'notChecked',
                            'field' => 'is-current-work'
                        ],
                    ],
                    'work-end-year' => [
                        'type' => 'range',
                        'options' => [
                            'from' => 1920,
                            'to' => date('Y'),
                        ],
                        'requireIf' => [
                            'condition' => 'notChecked',
                            'field' => 'is-current-work'
                        ],
                    ],
                ],

                //is erronous
                false,
                //expected
                [
                    'is-current-work' => true,
                    'work-end-month' => null,
                    'work-end-year' => null,
                ]
            ],

            'second set' => [
                //data
                [
                    'work-end-month' => '',
                    'work-end-year' => '',
                ],
                //rules
                [
                    'is-current-work' => [
                        'type' => 'boolean',
                    ],
                    'work-end-month' => [
                        'type' => 'range',
                        'options' => [
                            'from' => 1,
                            'to' => 12
                        ],
                        'requireIf' => [
                            'condition' => 'notChecked',
                            'field' => 'is-current-work'
                        ],
                    ],
                    'work-end-year' => [
                        'type' => 'range',
                        'options' => [
                            'from' => 1920,
                            'to' => date('Y'),
                        ],
                        'requireIf' => [
                            'condition' => 'notChecked',
                            'field' => 'is-current-work'
                        ],
                    ],
                ],
                //is erronous
                true,
                //expected
                [
                    'work-end-month' => 'work-end-month is required',
                    'work-end-year' => 'work-end-year is required',
                ]
            ],

            //test checked condition
            'third set' => [
                //data
                [
                    'join-newsletter' => 'on',
                    'email' => 'Harrisonifeanyichukwu@gmail.com',
                ],
                //rules
                [
                    'join-newsletter' => [
                        'type' => 'boolean',
                    ],
                    'email' => [
                        'type' => 'email',
                        'requireIf' => [
                            'condition' => 'checked',
                            'field' => 'join-newsletter'
                        ],
                    ],
                ],

                //is erronous
                false,
                //expected
                [
                    'join-newsletter' => true,
                    'email' => 'Harrisonifeanyichukwu@gmail.com',
                ]
            ],

            'fourth set' => [
                //data
                [
                    'join-newsletter' => 'on',
                    'email' => '',
                ],
                //rules
                [
                    'join-newsletter' => [
                        'type' => 'boolean',
                    ],
                    'email' => [
                        'type' => 'email',
                        'requireIf' => [
                            'condition' => 'checked',
                            'field' => 'join-newsletter'
                        ],
                        'hint' => 'you must enter your email to join'
                    ],
                ],

                //is erronous
                true,
                //expected
                [
                    'email' => 'you must enter your email to join',
                ]
            ],

            'fifth set' => [
                //data
                [
                    'email' => '',
                ],
                //rules
                [
                    'join-newsletter' => [
                        'type' => 'boolean',
                    ],
                    'email' => [
                        'type' => 'email',
                        'requireIf' => [
                            'condition' => 'checked',
                            'field' => 'join-newsletter'
                        ],
                        'hint' => 'you must enter your email to join',
                        'default' => ''
                    ],
                ],

                //is erronous
                false,
                //expected
                [
                    'join-newsletter' => false,
                    'email' => '',
                ]
            ],

            //test not equals condition
            'sixth set' => [
                //data
                [
                    'country' => 'ng',
                    'calling-code' => '',
                ],
                //rules
                [
                    'country' => [
                        'required' => true,
                        'type' => 'choice',
                        'options' => [
                            'choices' => array('ng', 'gb', 'us', 'gh')
                        ]
                    ],
                    //tell us your country code if you are not in nigeria, nigerians do not

                    //need to
                    'calling-code' => [
                        'requireIf' => [
                            'condition' => 'notEquals',
                            'field' => 'country',
                            'value' => 'ng'
                        ]
                    ]
                ],
                //is erronous
                false,

                //expected
                [
                    'country' => 'ng',
                    'calling-code' => null,
                ]
            ],

            'seventh set' => [
                //data
                [
                    'country' => 'gb',
                    'calling-code' => '',
                ],
                //rules
                [
                    'country' => [
                        'required' => true,
                        'type' => 'choice',
                        'options' => [
                            'choices' => array('ng', 'gb', 'us', 'gh')
                        ]
                    ],
                    //tell us your country code if you are not in nigeria, nigerians do not

                    //need to
                    'calling-code' => [
                        'requireIf' => [
                            'condition' => 'notEqual',
                            'field' => 'country',
                            'value' => 'ng'
                        ],
                        'hint' => 'tell us your country calling code'
                    ]
                ],
                //is erronous
                true,

                //expected error
                [
                    'calling-code' => 'tell us your country calling code',
                ]
            ],

            //test equals condition
            'eigth set' => [
                //data
                [
                    'country' => 'gb',
                ],
                //rules
                [
                    'country' => [
                        'required' => true,
                        'type' => 'choice',
                        'options' => [
                            'choices' => array('ng', 'gb', 'us', 'gh')
                        ]
                    ],
                    //tell us your salary demand if you are in nigeria, other countries
                    //are paid equal amount of $50,000 yearly
                    'salary-demand' => [
                        'requireIf' => [
                            'condition' => 'Equal',
                            'field' => 'country',
                            'value' => 'ng'
                        ],
                        'hint' => 'tell us your salary demand'
                    ]
                ],
                //is erronous
                false,

                //expected data
                [
                    'salary-demand' => null,
                ]
            ],

            'ninth set' => [
                //data
                [
                    'country' => 'ng',
                    'salary-demand' => '100000',
                ],
                //rules
                [
                    'country' => [
                        'required' => true,
                        'type' => 'choice',
                        'options' => [
                            'choices' => array('ng', 'gb', 'us', 'gh')
                        ]
                    ],
                    //tell us your salary demand if you are in nigeria, other countries
                    //are paid equal amount of $50,000 yearly
                    'salary-demand' => [
                        'requireIf' => [
                            'condition' => 'Equal',
                            'field' => 'country',
                            'value' => 'ng'
                        ],
                        'type' => 'money',
                        'hint' => 'tell us your salary demand'
                    ]
                ],
                //is erronous
                false,

                //expected data
                [
                    'salary-demand' => 100000,
                ]
            ],

            'tenth set' => [
                //data
                [
                    'country' => 'ng',
                    'salary-demand' => '',
                ],
                //rules
                [
                    'country' => [
                        'required' => true,
                        'type' => 'choice',
                        'options' => [
                            'choices' => array('ng', 'gb', 'us', 'gh')
                        ]
                    ],
                    //tell us your salary demand if you are in nigeria, other countries
                    //are paid equal amount of $50,000 yearly
                    'salary-demand' => [
                        'requireIf' => [
                            'condition' => 'Equal',
                            'field' => 'country',
                            'value' => 'ng'
                        ],
                        'type' => 'money',
                        'hint' => 'tell us your salary demand'
                    ]
                ],
                //is erronous
                true,

                //expected error
                [
                    'salary-demand' => 'tell us your salary demand',
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
        $this->expectException(DataSourceNotSetException::class);
        $instance = new Handler();
        $instance->execute();
    }

    /**
     * test that it throws error if executed with no rule set
    */
    public function testExecuteWithNoRulesSet()
    {
        $this->expectException(RulesNotSetException::class);
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
     * test that we can add extra fields to our data source
    */
    public function testAddField()
    {
        $data = [];
        $rules = [
            'first-name' => [
                'type' => 'text'
            ],
            'last-name' => [
                'type' => 'text'
            ],
        ];
        $instance = new Handler($data, $rules);
        $instance->addFields([
            'first-name' => 'Harrison',
            'last-name' => 'Ifeanyichukwu'
        ])
            ->execute();

        $this->assertTrue($instance->succeeds());
        $this->assertEquals('Harrison', $instance->first_name);
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
            foreach ($errors as $key => $value) {
                $this->assertEquals($value, $instance->getData($key));
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

    /**
     *@dataProvider filterTestDataProvider
    */
    public function testFilters(...$args)
    {
        $this->executeHandlerFeature(...$args);
    }

    /**
     *@dataProvider requireIfTestDataProvider
    */
    public function testRequireIfConditions(...$args)
    {
        $this->executeHandlerFeature(...$args);
    }

    /**
     * test the get error method
    */
    public function testGetErrorMethod()
    {
        $data = [];
        $rules = [
            'first-name' => [
                'type' => 'text'
            ],
        ];
        $instance = new Handler($data, $rules);
        $instance->execute();

        $this->assertTrue($instance->fails());

        //test that the get error method returns the first error message when given no key
        $this->assertEquals('first-name is required', $instance->getError());

        //test that it returns null for unknown key
        $this->assertNull($instance->getError('last-name'));
    }

    /**
     * test the get data method
    */
    public function testGetDataMethod()
    {
        $data = [
            'first-name' => 'Harrison'
        ];
        $rules = [
            'first-name' => [
                'type' => 'text'
            ],
        ];
        $instance = new Handler($data, $rules);
        $instance->execute();

        $this->assertTrue($instance->succeeds());

        //test that the get data method returns correctly the data for the given key
        $this->assertEquals('Harrison', $instance->getData('first-name'));

        //test that it throw exception if key is not known
        $this->expectException(DataNotFoundException::class);
        $instance->getData('last-name');
    }

    /**
     * test the getter method
    */
    public function testGetterMethod()
    {
        $data = [
            'first-name' => 'Harrison',
            'last_name' => 'Ifeanyichukwu'
        ];
        $rules = [
            'first-name' => [
                'type' => 'text'
            ],
            'last_name' => [
                'type' => 'text'
            ],
        ];
        $instance = new Handler($data, $rules);
        $instance->execute();

        $this->assertTrue($instance->succeeds());

        //test that we can access data directly on the instance
        $this->assertEquals('Ifeanyichukwu', $instance->last_name);

        //test that we can still access hyphen named data using underscores directly on the
        //instance
        $this->assertEquals('Harrison', $instance->first_name);

        //test that it throws error if key does not exist
        $this->expectException(DataNotFoundException::class);
        $instance->middle_name;
    }

    public function testUnknownDataTypeWarning()
    {
        $data = [
            'first-name' => 'Harrison',
            'last_name' => 'Ifeanyichukwu'
        ];
        $rules = [
            'first-name' => [
                'type' => 'unknown'
            ],
            'last_name' => [
                'type' => 'text'
            ],
        ];
        $instance = new Handler($data, $rules);

        $this->expectException(Warning::class);
        $instance->execute();
    }
}