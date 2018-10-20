<?php
declare(strict_types = 1);

namespace Forensic\Handler\Test;

use Forensic\Handler\Handler;
use PHPUnit\Framework\TestCase;
use Forensic\Handler\Exceptions\DataSourceNotRecognizedException;
use Forensic\Handler\Exceptions\DataSourceNotSetException;
use Forensic\Handler\Exceptions\RulesNotSetException;
use Forensic\Handler\Exceptions\KeyNotFoundException;
use PHPUnit\Framework\Error\Warning;
use Forensic\Handler\Exceptions\MissingParameterException;
use Forensic\Handler\Test\Helpers\DBChecker;
use Forensic\Handler\Exceptions\DBCheckerNotFoundException;
use stdClass;
use Forensic\Handler\Exceptions\StateException;
use Forensic\Handler\Exceptions\InvalidArgumentException;

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
                    'profile-picture' => [
                        'type' => 'file'
                    ]
                ],
                // is erronous
                true,
                //missing fields section
                [
                    'last_name' => 'last_name is required',
                    'languages' => 'languages field is required',
                    'profile-picture' => 'profile-picture is required'
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
                    'alpha-two' => 'Z',
                    'money' => '500',
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
                    'money' => [
                        'filters' => [
                            'numeric' => true,
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
                    'height' => 5.4,
                    'email' => 'Harrisonifeanyichukwu@gmail.com',
                    'website' => 'http://www.fjsfoundations.com',
                    'alpha-one' => 'A',
                    'alpha-two' => 'z',
                    'money' => 500
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
     * provides array of rules used in validating db checks
    */
    public function dbCheckResolutionTestDataProvider()
    {
        return [
            'exists resolution set' => [
                //data
                [
                    'id' => '2',
                    'country' => ''
                ],
                //rules
                [
                    'id' => [
                        'type' => 'positiveInt',
                        'check' => [
                            'if' => 'exists',
                            'query' => 'SELECT 1 FROM products WHERE {_this} = {_index}',
                            'err' => 'product with id {this} already exists'
                        ],
                    ],
                ],
                //expected resolutions
                [
                    'id' => 'exist'
                ],
            ],
            'does not/doesnt exists resolution set' => [
                //data
                [
                    'id' => '2',
                    'email' => 'Harrisonifeanyichukwu@gmail.com',
                    'languages' => array('php', 'javascript')
                ],
                //rules
                [
                    'id' => [
                        'check' => [
                            'if' => 'doesNotExists',
                            'entity' => 'products',
                            'err' => 'product with id {this} does not exist'
                        ],
                    ],
                    'email' => [
                        'type' => 'email',
                        'checks' => [
                            [
                                'if' => 'doesntExist',
                                'entity' => 'users',
                                'err' => 'user with email "{this}" not found'
                            ],
                        ],
                    ],
                    'country' => [
                        'required' => false,
                        //if it is supplied, check if the country is in our database list
                        'check' => [
                            'if' => 'notExists',
                            'err' => '{this} is not a recognised country',
                            'query' => 'SELECT 1 FROM countries WHERE value = ?',
                            'params' => array('{this}'),
                        ],
                    ],
                    'languages' => [
                        'required' => false,
                        //if it is supplied, check if the language is in our database list
                        'check' => [
                            'if' => 'notExists',
                            'err' => '{this} is not a recognised language',
                            'query' => 'SELECT 1 FROM languages WHERE value = ?',
                            'params' => array('{this}'),
                        ]
                    ]
                ],
                //expected resolutions
                [
                    'id' => 'notexist',
                    'email' => 'notexist'
                ]
            ],
        ];
    }

    /**
     * provides data used in testing missing parameter db check exception
    */
    public function dbCheckMissingParameterExceptionTestDataProvider()
    {
        return [
            'missing if parameter exception set' => [
                //data
                [
                    'email' => 'Harrisonifeanyichukwu@gmail.com'
                ],
                //rule
                [
                    'email' => [
                        'type' => 'email',
                        'check' => [
                            'entity' => 'Users'
                        ],
                    ],
                ],
            ],
            'missing entity and query parameter exception set' => [
                //data
                [
                    'email' => 'Harrisonifeanyichukwu@gmail.com'
                ],
                //rule
                [
                    'email' => [
                        'type' => 'email',
                        'check' => [
                            'if' => 'exists',
                        ],
                    ],
                ],
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

        $instance->execute();
        $this->assertTrue($instance->succeeds());

        //test that calling the execute method multiple times has no side effect
        $instance->execute();
        $this->assertTrue($instance->succeeds());
    }

    /**
     * test that it throws state Exception if we access the succeeds or fails method
     * without executing the instance first
    */
    public function testStateException()
    {
        $instance = new Handler($this->getSimpleData(), $this->getSimpleRules());

        $this->expectException(StateException::class);
        $instance->succeeds();

        $this->expectException(StateException::class);
        $instance->fails();
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
     * test that the getErrors method returns all errors as array
    */
    public function testGetErrorsMethod()
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

        $this->assertEquals([
            'first-name' => 'first-name is required',
        ], $instance->getErrors());
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
        $this->expectException(KeyNotFoundException::class);
        $instance->getData('last-name');
    }

    /**
     * test that the getAllData method returns all data as an array
    */
    public function testGetAllDataMethod()
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

        $this->assertEquals([
            'first-name' => 'Harrison'
        ], $instance->getAllData());
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
        $this->expectException(KeyNotFoundException::class);
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

    /**
     * test file handling
    */
    public function testFileHandling()
    {
        $rules = [
            'picture' => [
                'type' => 'file'
            ],
        ];
        $_FILES['picture'] = getTestFileDetails('file1.jpg', 'image/jpeg');
        $instance = new Handler([], $rules);
        $instance->execute();

        $this->assertTrue($instance->succeeds());
        $this->assertEquals('file1.jpg', $instance->picture);
    }

    /**
     * test multi file handling
    */
    public function testMultiFileHandling()
    {
        $filenames = array('file1.jpg', 'file2.txt', 'file3');
        $mimes = array('image/jpeg', 'text/plain', 'octet/stream');
        $error_codes = array_fill(0, 3, UPLOAD_ERR_OK);

        $rules = [
            'files' => [
                'type' => 'file'
            ],
        ];

        $_FILES['files'] = getTestMultiFileDetails($filenames, $mimes, $error_codes);
        $instance = new Handler([], $rules);
        $instance->execute();

        $this->assertTrue($instance->succeeds());
        $this->assertEquals(array('file1.jpg', 'file2.txt', 'file3'), $instance->files);
    }

    /**
     * test multi file handling with moveTo option
    */
    public function testMultiFileHandlingWithMoveToOption()
    {
        $filenames = array('file1.jpg', 'file2.txt', 'file3');
        $mimes = array('image/jpeg', 'text/plain', 'octet/stream');
        $error_codes = array_fill(0, 3, UPLOAD_ERR_OK);

        $rules = [
            'files' => [
                'type' => 'file',
                'options' => [
                    'moveTo' => 'tests/Helpers'
                ]
            ],
        ];

        $_FILES['files'] = getTestMultiFileDetails($filenames, $mimes, $error_codes);
        $instance = new Handler([], $rules);
        $instance->execute();

        $this->assertTrue($instance->succeeds());
        $files = $instance->files;

        $this->assertNotEquals($filenames, $files);
        foreach($files as $index => $file)
        {
            $this->assertFileExists('tests/Helpers/' . $file);
            rename('tests/Helpers/' . $file, 'tests/Helpers/' . $filenames[$index]);
        }
    }

    /**
     * test _index validation error reference
    */
    public function testValidationValueIndexReference()
    {
        $data = [
            'colors' => array('orange', 'white', 'red', 'london')
        ];
        $rules = [
            'colors' => [
                'options' => [
                    'regex' => [
                        'test' => '/^(orange|white|red|black|green|purple|voilet)$/',
                        'err' => 'color number {_index} is not a valid color'
                    ],
                ],
            ],
        ];
        $instance = new Handler($data, $rules);
        $instance->execute();

        $this->assertFalse($instance->succeeds());
        $this->assertEquals(
            'color number 4 is not a valid color',
            $instance->getError('colors')
        );
    }

    /**
     * test that the getDBChecks returns array of db checks and throws error if given field
     * key does not exists
    */
    public function testGetDBChecks()
    {
        $instance = new Handler($this->getSimpleData(), $this->getSimpleRules(), null,
            new DBChecker()
        );
        $instance->execute();

        $this->assertTrue(is_array($instance->getDBChecks('first-name')));
        $this->expectException(KeyNotFoundException::class);

        $instance->getDBChecks('unknown');
    }

    /**
     *@dataProvider dbCheckResolutionTestDataProvider
    */
    public function testDBCheckResolution(array $data, array $rules, array $expected)
    {
        $instance = new Handler($data, $rules, null, new DBChecker());
        $instance->execute();

        foreach($expected as $field => $value)
        {
            $db_checks = $instance->getDBChecks($field);
            foreach($db_checks as $db_check)
            {
                $this->assertEquals($db_check['if'], $value);
            }
        }
    }

    /**
     * test that a warning is issued if the db check parameter is not recognised
    */
    public function testUnknownDBCheckIfWarning()
    {
        $data = [
            'email' => 'Harrisonifeanyichukwu@gmail.com'
        ];
        $rules = [
            'email' => [
                'type' => 'email',
                'check' => [
                    'if' => 'unknown',
                    'entity' => 'Users'
                ],
            ],
        ];
        $instance = new Handler($data, $rules, null, new DBChecker());

        $this->expectException(Warning::class);
        $instance->execute();
    }

    /**
     * test that missing parameter exception is thrown if a certain db check rule parameter is
     * missing
     *
     *@dataProvider dbCheckMissingParameterExceptionTestDataProvider
    */
    public function testDBCheckMissingParameterException(array $data, array $rules)
    {
        $instance = new Handler($data, $rules, null, new DBChecker());
        $this->expectException(MissingParameterException::class);

        $instance->execute();
    }

    /**
     * test that it throws exception if no db checker implementation is set and there is
     * db check rule given
    */
    public function testDBCheckerNotFoundException()
    {
        $data = [
            'email' => 'Harrisonifeanyichukwu@gmail.com'
        ];
        $rules = [
            'email' => [
                'type' => 'email',
                'check' => [
                    'if' => 'exists',
                    'entity' => 'users',
                ],
            ],
        ];

        $this->expectException(DBCheckerNotFoundException::class);
        $instance = new Handler($data, $rules);
        $instance->execute();
    }

    /**
     * test that the resolve model field name works as expected
    */
    public function testResolveModelFieldName()
    {
        $instance = new Handler($this->getSimpleData(), $this->getSimpleRules());

        $this->assertEquals('first_name', $instance->resolveModelFieldName('first-name'));

        $instance->modelCamelizeFields(true);
        $this->assertEquals('firstName', $instance->resolveModelFieldName('first-name'));
    }

    /**
     * test that the map to model function correctly maps the whole data to the given
     * model
    */
    public function testMapDataToModel()
    {
        $model = new stdClass();
        $instance = new Handler($this->getSimpleData(), $this->getSimpleRules());

        $instance->execute();

        $instance->mapDataToModel($model);

        $all_data = $instance->getAllData();

        foreach($all_data as $field => $value)
        {
            $model_field = $instance->resolveModelFieldName($field);
            $this->assertEquals($value, $model->{$model_field});
        }
    }

    /**
     * test that invalid argument exception is thrown if the argument is not an object
    */
    public function testMapDataToModelInvalidArgumentException()
    {
        $model = new stdClass();
        $instance = new Handler($this->getSimpleData(), $this->getSimpleRules());

        $instance->execute();

        $this->expectException(InvalidArgumentException::class);
        $instance->mapDataToModel([]);
    }

    /**
     * test that we cannot map data to our model if our is in errornous state
    */
    public function testMapDataToModelStateException()
    {
        $model = new stdClass();
        $instance = new Handler([], ['first-name' => ['type' => 'text']]);

        $instance->execute();

        $this->assertTrue($instance->fails());
        $this->expectException(StateException::class);
        $instance->mapDataToModel(new stdClass());
    }

    /**
     * test that we can skip some fields while mapping data
    */
    public function testMapDataToModelWithSomeFieldsSkipped()
    {
        $model = new stdClass();
        $instance = new Handler($this->getSimpleData(), $this->getSimpleRules());
        $instance->execute();

        $fields_to_skip = ['first-name', 'last-name'];
        $instance->modelSkipFields($fields_to_skip);

        $instance->mapDataToModel($model);

        foreach($fields_to_skip as $field)
        {
            $this->assertObjectNotHasAttribute($instance->resolveModelFieldName($field), $model);
        }
    }

    /**
     * test that we can rename some fields while mapping data
    */
    public function testMapDataToModelWithSomeFieldsRenamed()
    {
        $model = new stdClass();
        $instance = new Handler($this->getSimpleData(), $this->getSimpleRules());
        $instance->execute();

        $fields_to_rename = ['first-name' => 'firstName', 'last-name' => 'secondName'];
        $instance->modelRenameFields($fields_to_rename);

        $instance->mapDataToModel($model);

        foreach($fields_to_rename as $old_name => $new_name)
        {
            $this->assertObjectHasAttribute($instance->resolveModelFieldName($new_name), $model);
            $this->assertObjectNotHasAttribute($instance->resolveModelFieldName($old_name), $model);

            $this->assertEquals($instance->getData($old_name),
                $model->{$instance->resolveModelFieldName($new_name)});
        }
    }
}