# Handler

[![Build Status](https://travis-ci.org/harrison-ifeanyichukwu/handler.svg?branch=master)](https://travis-ci.org/harrison-ifeanyichukwu/handler)
[![Coverage Status](https://coveralls.io/repos/github/harrison-ifeanyichukwu/handler/badge.svg?branch=master)](https://coveralls.io/github/harrison-ifeanyichukwu/handler?branch=master)
[![semantic-release](https://img.shields.io/badge/%20%20%F0%9F%93%A6%F0%9F%9A%80-semantic--release-e10079.svg)](https://github.com/semantic-release/semantic-release)
![Packagist](https://img.shields.io/packagist/dt/forensic/handler.svg)

Forensic Handler is a php module that sits independently between the controller and the model, performing request data validation, serialization and integrity checks. It is easy to setup and independent of any php framework and ORMs.

It makes the validation process easy and requires you to just define the data validation rules which are just php arrays.

The most interesting part is how easy it is to validate array of field data and files and the wide range of validation rule types that it affords you. It is also extensible so that you can define more validation rules if the need be.

Regarding database integrity checks, it is extensible enough to leave the db check implementation up to you by defining an abstract `DBCheckerAbstract` class. This makes it not tied to any framework or ORM. It is quite easy to [implement](#implementing-the-dbcheckerabstract-validation).

## Getting Started

**Install via composer**:

```bash
composer require forensic/handler
```

## Usage Example

**Coding the auth handler**:

```php
/**
 * file AuthHandler.php
*/
//make sure composer autoloader is loaded

namespace app\Handler;

use Forensic\Handler\Handler as BaseHandler;
use SomeNamespace\Model\UserModel; //our model

class AuthHandler extends BaseHandler
{
    public function getDB()
    {
        //return db orm
    }

    /**
     * executes signup
     *@param array|string [$source = 'post'] - the source of the data. can also be an array
    */
    public function executeSignup($source = 'post')
    {
        $rules = [
            //email field rule.
            'email' => [
                'type' => 'email',
                'err' => '{this} is not a valid email address'
            ],
            'password1' => [
                'type' => 'password',
            ],
            'password2' => [
                'type' => 'password',
                'matchWith' => '{password1}'
            ],
        ];

        $this->setSource($source)->setRules($rules);

        if (!$this->execute())
            return $this->succeeds(); //return immediately if there are errors

        /*
        * check if user exists, so that we dont create same user again
        * we can check using the model, or execute a prepared separate query. we could also
        * define this integrity check right in the rules above, only that we must implement
        * the DBCheckerAbstract class which will be shown later
        */
        $query = 'SELECT id FROM users WHERE email = ? AND password = ?';
        if ($this->getDB()->select($query, array($this->email, $this->password1)))
        {
            $this->setError('email', 'User already exists, please login instead');
            return $this->succeeds(); //return immediately if there is error
        }

        //create user
        $user = new UserModel();

        //do not copy password2 and rename password1 to just password
        $this->modelSkipField('password2')->modelRenameField('password1', 'password');
        $this->mapDataToModel($user)->save(); // it returns the model

        //set the new user id
        $this->setData('userId', $user->id);

        return $this->succeeds();
    }
}
```

**Coding the Controller**:

```php
/**
 * file auth controller
 *
*/
namespace app\Controller;

use SomeNamespace\Request; //likely symphony or laravel
use SomeNamespace\JsonResponse; //same here

use SomeNamespace\SomeController as BaseController; //same here
use app\Handler\AuthHandler;

class AuthController extends BaseController
{
    public function signup(Request $request, Response $response, AuthHandler $handler)
    {
        if ($handler->executeSignup())
            return $response([
                    'status' => 'success',
                    'data' => [
                        'userId' => $handler->userId
                    ],
                ]);
        else
            return $response([
                    'status' => 'failed',
                    'data' => [
                        'errors' => $handler->getErrors()
                    ],
                ]);
    }
}
```

## Validation Rule Formats

Validation rules are defined as arrays keyed by the their field names. Each field array should have a `type` property that defines the type of validation. Optional fields should have a `required` key property set to `false`. Every other rule details must go into the `options` array key except the `filters`, `check` and `checks` database rules.

During validation, there are some special ways of referencing the validation principals. `{_this}` references the current field under validation, `{this}` references the current field value under validation, while `{_index}` references the current field value index position (in the case of validating array of fields).

Others include `{CURRENT_DATE}`, `{CURRENT_YEAR}`, `{CURRENT_TIME}`.

**Example**:

```php
$rules = [
    'first-name' => [
        'type' => 'text',
        'options' => [
            'min' => 3, //the name should be at least 3 letter charaters
            'minErr' => 'first name should be at least 3 charaters length'
        ],
    ],
    //we are expecting an array of favorite colors
    'favorite-colors' => [
        'type' => 'choice',
        'filters' => [
            'toLower' => true, //convert the colors to lowercase
        ],
        'options' => [
            'choices' => array('green', 'white', 'blue', 'red', 'violet', 'purple'),
            'err' => '{this} is not a color', // or 'color {_index} is not a color'
        ],
    ],
]
```

## Validation Filters

Filters are applied to the field values prior to validations. You can use filters to convert values to lower or upper case, cast it to a numeric value, avoid stripping of tags (which is true by default) and lots more. The available filters include:

1. **decode**: defaults to true

2. **trim**: defaults to true

3. **stripTags**: defaults to true

4. **stripTagsIgnore**: defaults to empty string

5. **numeric**: defaults to false

6. **toUpper**: defaults to false

7. **toLower**: defaults to false

**Usage Example**:

```php
$rules = [
    'country' => [
        'filters' => [
            'toLower' => true //convert to lowercase
        ],
    ],
    'comment' => [
        'filter' => [
            'stripTagsIgnore' => '<p><br>'
        ],
    ],
];
```

## Validation Rule Types

The module defines lots of validation rule types that covers a wide range of validation requirements. These includes the following:

- [Limiting Rule Validation](#limiting-rule-validation)

- [Regex Rule Validation](#regex-rule-validation)

- [MatchWith Rule Validation](#matchwith-rule-validation)

- [Date Type Validation](#date-type-validation)

- [Range Type Validation](#range-type-validation)

- [Choice Type Validation](#choice-type-validation)

- [Numeric Type Validation](#numeric-type-validation)

- [Password Type Validation](#password-type-validation)

- [File Type Validation](#file-type-validation)

- [Image File Type Validation](#image-file-type-validation)

- [Audio File Type Validation](#audio-file-type-validation)

- [Video File Type Validation](#video-file-type-validation)

- [Media File Type Validation](#media-file-type-validation)

- [Document File Type Validation](#document-file-type-validation)

- [Archive File Type Validation](#archive-file-type-validation)

### Limiting Rule Validation

The limiting rule validation option touches every validation. It is where we can define the limiting length of a string or value. These includes the **min**, **max**, **gt** (greater than) and **lt** (less than) options.

**Example**:

```php
$rules = [
    'first-name' => [
        'type' => 'text',
        'options' => [
            'min' => 3,
            'minErr' => 'first name should be at least 3 characters length',
            'max' => 15,
        ]
    ],
    'favorite-integer' => [
        'type' => 'positiveInteger',
        'options' => [
            'lt' => 101, //should be less than 101, or max of 100.
        ]
    ],
    'date-of-birth' => [
        'type' => 'date',
        'options' => [
            'min' => '01-01-1990', //only interested in people born on or after 01-01-1990
            'max' => '{CURRENT_DATE}'
        ]
    ],
];
```

### Regex Rule Validation

It is quite easy to carry out different flavours of regex rule tests on our data. There are four kinds of regex rules. These include single **regex** test, **regexAny**, **regexAll**, and **regexNone** tests.

For **regex** type, it must match the test, otherwise it is flagged as error. For **regexAny**, at least one of the tests must match. For **regexAll**, all regex tests must match. For **regexNone**, none of the regex tests should match.

**Example**:

```php
$rules = [
    'first-name' => [
        'type' => 'text',
        'regexAll' => [
            //name must start with letter
            [
                'test' => '/^[a-z]/i',
                'err' => 'name must start with an alphabet'
            ],
            //only aphabets, dash and apostrophe is allowed in name
            [
                'test' => '/^[-a-z\']+$/',
                'err' => 'only aphabets, dash, and apostrophe is allowed in name'
            ]
        ]
    ],
    'country' => [
        'type' => 'text',
        'options' => [
            'regex' => [
                'test' => '/^[a-z]{2}$/',
                'err' => '{this} is not a 2-letter country iso-code name'
            ]
        ],
    ],
    'phone-number' => [
        'type' => 'text',
        'options' => [
            'regexAny' => [
                'tests' => [
                    //phone number can match nigeria mobile number format
                    '/^0[0-9]{3}[-\s]?[0-9]{3}[-\s]?[0-9]{4}$/',

                    //phone number can match uk mobile number format
                    '/^07[0-9]{3}[-\s]?[0-9]{6}$/'
                ],
                'err' => 'only nigeria and uk number formats are accepted'
            ]
        ]
    ],
    'favorite-colors' => [
        'options' => [
            'regexNone' => [
                //we dont accept white as a color
                [
                    'test' => '/^white$/i',
                    'err' => '{this} is not an acceptable color'
                ],
                //we dont accept black either
                [
                    'test' => '/^black$/i',
                    'err' => '{this} is not an acceptable color'
                ],
            ],
        ],
    ],
]
```

### MatchWith Rule Validation

This rule is handy when you want to make sure that a field's value matches another field's value such as in password confirmation fields as well as email and phone confirmation scenerios.

**Example**:

```php
$rules = [
    'password1' => [
        'type' => 'password'
    ],
    'password2' => [
        'type' => 'password',
        'options' => [
            'matchWith' => '{password1}', //reference password1 value
            'err' => 'Passwords do not match'
        ],
    ],
];
```

### Date Type Validation

To validate dates, set the type property to *'date'*. You can specify [limiting rules](#limiting-rule-validation) that validates if the date is within a given limited range.

### Range Type Validation

To validate field as a range of value, set the type property to **range**. The range type accepts three more options keys, which are **from**, **to** and the **step** optional key.

**Example**:

```php
$rules = [
    'day' => [
        'type' => 'range',
        'options' => [
            'from' => 1,
            'to' => 31,
        ],
    ],
    'month' => [
        'type' => 'range',
        'options' => [
            'from' => 1,
            'to' => 12,
        ],
    ],
    'year' => [
        'type' => 'range',
        'options' => [
            'from' => 1950,
            'to' => '{CURRENT_YEAR}',
        ],
    ],
    'even-number' => [
        'type' => 'range',
        'options' => [
            'from' => 0,
            'to' => 100,
            'step' => 2,
            'err' => '{this} is not a valid even number between 0-100'
        ],
    ]
];
```

### Choice Type Validation

To validate field against a choice of options, set the type property to **choice**. The choice type accepts **choices** options key which is an array of acceptable choice options. The [range](#range-type-validation) type makes use of this type validator internally.

**Example**:

```php
$rules = [
    'country' => [
        'type' => 'choice',
        'options' => [
            'choices' => array('ng', 'gb', 'us', 'ca', ...),// array of country codes,
            'err' => '{this} is not a valid country code'
        ],
    ],
];
```

### Email Type Validation

To validate email addresses, set the type property to type `email`.

```php
$rules = [
    'email' => [
        'type' => 'email'
    ],
];
```

### URL Type Validation

To validate url, set the type property to type `url`.

```php
$rules = [
    'website' => [
        'type' => 'url'
    ],
];
```

### Numeric Type Validation

To validate numbers, integers whether positive or negative, the following types are avaliable: `float`, `number`, 'positiveFloat' or `pFloat`, `negativeFloat`, or `nFloat`, `money` (same as float), `integer`, `int`, `pInt`, `nInt`, `negativeInt`, etc. It does not matter the naming convention used.

```php
$rules = [
    'favorite-number' => [
        'type' => 'number'
    ],
    'user-id' => [
        'type' => 'positiveInt', //starts from 1.  note that 1.5 would fail. but 2 is valid
    ]
];
```

### Password Type Validation

Password type validation is more like text validation except that some limiting rules and regex rules were added. The default validation implementation is that passwords must be at least 8 charaters long, and 28 characters max. It must contain at least two alphabets and at least two non alphabets. You can override this default if you like.

The rule is like below

```php
[
    'min' => 8,
    'max' => 28,
    'regexAll' => [
        //password should contain at least two alphabets
        [
            'test' => '/[a-z].*[a-z]/i',
            'err' => 'Password must contain at least two letter alphabets'
        ],
        //password should contain at least two non letter alphabets
        [
            'test' => '/[^a-z].*[^a-z]/i',
            'err' => 'Password must contain at least two non letter alphabets'
        ],
    ],
];
```

### File Type Validation

The module can validate files, including the integrity of file mime types. It offers wide flavours of file validation such as images, videos, audios, documents and archives.

The simplest file validation rule is like below

```php
$rules => [
    'picture' => [
        'type' => 'file',
        'options' => [
            //some limiting rules regarding file size
            'min' => '50kb' //it will be converted accurately
        ]
    ],
];
```

You can define an absolute path to move the file to using the **moveTo** option. when the file is being, a hashed name is computed for it, and the field name is replaced with the computed hash value.

```php
use Forensic\Handler\Handler;

$move_to = getcwd() . '/storage/media/pictures';
$rules => [
    'picture' => [
        'type' => 'file',
        'options' => [
            'moveTo' => $move_to
        ],
    ],
];

$handler = new Handler('post', $rules);
$handler->execute();

if ($handler->succeeds())
{
    $file_name = $handler->picture; //the computed hash name is stored in the field
    $file_abs_path = $move_to . '/' . $file_name;
}
```

### Dealing With Multi-Value Fields and Files

The handler can process multi-value fields and file fields. The field values are stored inside arrays after processing.

**Example**:

```php
$move_to = getcwd() . '/storage/media/pictures';
$rules => [
    'pictures' => [
        'type' => 'file',
        'options' => [
            'max' => '400kb',
            'moveTo' => $move_to
        ],
    ],
];

$handler = new Handler('post', $rules);
$handler->execute();

if ($handler->succeeds())
{
    array_walk(function($file_name) {
        /**
         * we walk through all the files, and do whatever we want.
        */
        $abs_path = $move_to . '/' . $file_name; // abs path of current file.

    }, $handler->pictures);
}
```

### Specifying Accepted File Mimes Extensions

You can specify the accepted mime file extensions during validation. Note that the handler has a `FileExtensionDetector` module that detects file extension based on its magic number. Hence, limiting file extension spoofing errors. Please note that the current list of file magic numbers are still being updated, you can help us by reporting to us more magic bytes codes that are missing.

To specify accepted mimes, use the `mimes` options.

**Example**:

```php
$move_to = getcwd() . '/storage/media/pictures';
$rules => [
    'pictures' => [
        'type' => 'file',
        'options' => [
            'max' => '400kb',
            'moveTo' => $move_to,
            'mimes' => array('jpeg', 'png') //we only accept jpeg and png files. no gif,
            'mimeErr' => 'we only accept jpeg and png images'
        ],
    ],
];
```

### Image Type Validation

The shorter way to validate images is to use the `image` type option. The accepted mimes for images include **JPEG**, **PNG** and **GIF**.

```php
$move_to = getcwd() . '/storage/media/pictures';
$rules => [
    'pictures' => [
        'type' => 'image',
        'options' => [
            'max' => '400kb',
            'moveTo' => $move_to,
        ],
    ],
];
```

### Audeo File Type Validation

The shorter way to validate audios is to use the `audio` type option. The accepted mimes for audios include **MP3** and others.

```php
$move_to = getcwd() . '/storage/media/audeos';
$rules => [
    'pictures' => [
        'type' => 'audeo',
        'options' => [
            'max' => '400mb',
            'moveTo' => $move_to,
        ],
    ],
];
```

### Video File Type Validation

The shorter way to validate videos is to use the `video` type option. The accepted mimes for videos include **MP4**, **OGG**, **MOVI**, and others.

```php
$move_to = getcwd() . '/storage/media/videos';
$rules => [
    'pictures' => [
        'type' => 'video',
        'options' => [
            'max' => '400mb',
            'moveTo' => $move_to,
        ],
    ],
];
```

### Media File Type Validation

The shorter way to validate media files (videos, images and audios) is to use the `media` type option. The accepted mimes is a combination of **video**, **image** and **audio** mimes.

```php
$move_to = getcwd() . '/storage/media';
$rules => [
    'pictures' => [
        'type' => 'media',
        'options' => [
            'max' => '400mb',
            'moveTo' => $move_to,
        ],
    ],
];
```

### Document File Type Validation

The shorter way to validate documents is to use the `document` type option. The accepted mimes for documents include **DOCX**, **PDF** and **DOC**, and others.

```php
$move_to = getcwd() . '/storage/documents';
$rules => [
    'pictures' => [
        'type' => 'document',
        'options' => [
            'max' => '4mb',
            'moveTo' => $move_to,
        ],
    ],
];
```

### Archive File Type Validation

The shorter way to validate archive files is to use the `archive` type option. The accepted mimes for archives include **ZIP**, **TAR.GZ** and **TAR**, and others.

```php
$move_to = getcwd() . '/storage/archives';
$rules => [
    'pictures' => [
        'type' => 'archive',
        'options' => [
            'max' => '50mb',
            'moveTo' => $move_to,
        ],
    ],
];
```

### Implementing the DBCheckerAbstract Validation

To enable database integrity checks, you must implement two methods on the `DBCheckerAbstract` class which are the `buildQuery` and `execute` methods. Then you have to supply an instance of your concrete class as the fourth argument to the Handler.

Below shows how you can do this in Laravel:

```php
<?php
namespace app\Handler;

use Forensic\Handler\Abstracts\DBCheckerAbstract;
use Illuminate\Support\Facades\DB;

class DBChecker extends DBCheckerAbstract
{
    /**
     * construct query from the given options
     *
     *@param array $options - array of options
     * the options array contains the following fields.
     * 'entity': is the database table
     * 'params': which is array of parameters. defaults to empty array
     * 'query': which is the query to run. defaults to empty string
     *  'field': if the query parameter is empty string, then there is the field parameter
     * that refers to the database table column to check
    */
    protected function buildQuery(array $options): string
    {
        $query = $options['query'];

        //if the query is empty string, we build it according to our orm
        if ($query === '')
        {
            //build the query
            $query = 'SELECT * FROM ' . $options['entity'] . ' WHERE ' .
                $options['field'] . ' = ?';
        }
        return $query;
    }

    /**
     * executes the query. the execute method should return array of result or empty
     * array if there is no result
    */
    protected function execute(string $query, array $params, array $options): array
    {
        return DB::select($query, $params);
    }
}
```

Then we can define our own `BaseHandler` and supply an instance of our concrete class as fourth argument to the parent `Handler` module constructor as shown below:

```php
//file BaseHandler
namespace app\Handler;

use Forensic\Handler\Handler as ParentHandler;

class BaseHandler extends ParentHandler
{
    public function construct($source = null, array $rules = null)
    {
        parent::__construct($source, $rules, null, new DBChecker());
    }
}
```

Hence forth, we can now use 'check' and 'checks' rule options like shown below:

```php
// file AuthHandler.php

namespace app\Handler;

use app\Model\UserModel; //our model

class AuthHandler extends BaseHandler
{
    /**
     * executes signup
     *@param array|string [$source = 'post'] - the source of the data. can also be an array
    */
    public function executeSignup($source = 'post')
    {
        $rules = [
            //email field rule.
            'email' => [
                'type' => 'email',
                'err' => '{this} is not a valid email address',

                //db check rule goes here
                'check' => [
                    'if' => 'exists',
                    'entity' => 'users',
                    'field' => 'email',
                    'err' => 'User with email {this} already exists, login instead',
                ]
            ],
            'password1' => [
                'type' => 'password',
            ],
            'password2' => [
                'type' => 'password',
                'matchWith' => '{password1}'
            ],
        ];

        $this->setSource($source)->setRules($rules);

        if (!$this->execute())
            return $this->succeeds(); //return immediately if there are errors

        //create user
        $user = new UserModel();

        //do not copy password2 and rename password1 to just password
        $this->modelSkipField('password2')->modelRenameField('password1', 'password');
        $this->mapDataToModel($user)->save(); // it returns the model

        //set the new user id
        $this->setData('userId', $user->id);

        return $this->succeeds();
    }
}
```

### Defining Check and Checks Integrity Rules

The `check` option defines a single database integrity check while the `checks` option defines array of database integrity checks.

If there is no `query` option defined in the check array, there should be a `field` and `entity` options specified to help build the simple query. If the `field` option is omitted, it will default to the current field name under validation; However, if the field value is an integer, it will default to **id** rather than the field name.

To illustrate, below is an example:

```php
$rules = [
    'userid' => [
        'type' => 'positiveInt',
        'check' => [
            'if' => 'notExist',
            'entity' => 'users'
            //since no field is defined, it will defualt to id rather than userid
            //because field value is an integer. also, the params array will default
            //to array({this})
        ]
    ],
    'email' => [
        'type' => 'email',
        'checks' => [
            //first check
            [
                'if' => 'exists',
                'entity' => 'users'
                //since no field is defined, it will defualt to email
            ],
            //more checks goes here
        ]
    ],
    'country' => [
        'type' => 'text',
        'checks' => [
            //first check
            [
                'if' => 'notExist',
                'query' => 'SELECT * from countries WHERE value = ?',
                'params' => array('{this}'),
                'err' => '{this} is not recognised as a country in our database'
            ],
        ],
    ],
];
```

### Writing Your Custom Validators

The module is built to be extensible such that you can define more validator methods and use your own custom rule types. You would need to understand some basic things on how the package works. Insecting the `ValidatorInterface` and the `Validator` class files is a nice place to start. Below shows how this can be easily achieved.

**Define our custom validator, it inherits from the package validator**:

```php
<?php
//file CustomValidator.php
namespace app\Handler;

use Forensic\Handler\Validator;

class CustomValidator extends Validator
{
    protected function validateName(bool $required, string $field, $value,
        array $options, int $index = 0): bool
    {
        $options['min'] = 3;
        $options['max'] = 15;
        $options['regexAll'] = [
            //only alphabets dash and apostrophe is allowed in names
            [
                'test' => '/^[-a-z\']$/i',
                'err' => 'only alphabets, hyphen and apostrophe allowed in names'
            ]
            //name must start with at least two alphabets
            [
                'test' => '/^[a-z]{2,}/i',
                'err' => 'name must start with at least two alphabets'
            ],
        ];
        return $this->validateText($required, $field, $value, $options, $index);
    }
}
```

Then we can define our own `BaseHandler` that integrates the newly **name** type validator like shown below:

```php
//file BaseHandler
namespace app\Handler;

use Forensic\Handler\Handler as ParentHandler;

class BaseHandler extends ParentHandler
{
    public function construct($source = null, array $rules = null)
    {
        parent::__construct($source, $rules, null, new DBChecker());
    }

    /**
     *@override. override the parent class method
    */
    public function getRuleTypesMethodMap(): array
    {
        return array_merge(parent::getRuleTypesMethodMap(), [
            'name' => 'validateName'
        ]);
    }
}
```

Hence forth, we can now use  the **name** type to validate names like shown below:

```php
// file ProfileHandler.php

namespace app\Handler;

use app\Model\UserModel; //our model

class ProfileHandler extends BaseHandler
{
    /**
     * updates user profile
     *@param array|string [$source = 'post'] - the source of the data. can also be an array
    */
    public function updateProfile($source = 'post')
    {
        $rules = [
            //email field rule.
            'id' => [
                'type' => 'positiveInteger',

                //db check rule goes here
                'check' => [
                    'if' => 'doesNotExist',
                    'entity' => 'users',
                    'err' => 'No user found with id {this}',
                ]
            ],
            'first-name' => [
                'type' => 'name',
            ],
            'last-name' => [
                'type' => 'name',
            ],
            'middle-name' => [
                'type' => 'name',
                'required' => false,
                'default' => ''
            ]
        ];
        //more codes below
    }
}
```