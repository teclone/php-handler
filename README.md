# Handler

[![Build Status](https://travis-ci.org/harrison-ifeanyichukwu/handler.svg?branch=master)](https://travis-ci.org/harrison-ifeanyichukwu/handler)
[![Coverage Status](https://coveralls.io/repos/github/harrison-ifeanyichukwu/handler/badge.svg?branch=master)](https://coveralls.io/github/harrison-ifeanyichukwu/handler?branch=master)
[![semantic-release](https://img.shields.io/badge/%20%20%F0%9F%93%A6%F0%9F%9A%80-semantic--release-e10079.svg)](https://github.com/semantic-release/semantic-release)
![Packagist](https://img.shields.io/packagist/dt/forensic/handler.svg)

Forensic Handler is a php module that sits independently between the controller and the model, performing request data validation, serialization and integrity checks. It is easy to setup and independent of any php framework and ORMs.

It makes the validation process easy and requires you to just define the data validation rules which are just php arrays.

The most interesting part is how easy it is to validate array of field data and files and the wide range of validation rule types that it affords you. It is also extensible so that you can define more validation rules if the need be. See [How to Write Your Custom Validation Types](#how-to-write-your-custom-validation-types) for instructions

Regarding database integrity checks, it is extensible enough to leave the db check implementation up to you by defining an abstract `DBCheckerAbstract` class. This makes it not tied to any framework or ORM. See [How To Implement the DBCheckerAbstract Interface](#how-to-implement-the-dbcheckerabstract-interface) for instructions.

## Getting Started

**Install via composer**:

```bash
composer require forensic/handler
```

## Simple Usage Example

**The Handler**:

```php
/**
 * file AuthHandler.php
*/
//make sure composer autoloader is loaded

namespace app\Handler;

use Forensic\Handler\Handler as BaseHandler;
use app\Model\UserModel; //our model

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
        $result = $this->getDB()->select($query, array($this->email, $this->password1));
        if (count($result) > 0)
        {
            $this->setError('email', 'User already exists, please login instead');
            return $this->succeeds(); //return immediately if there is error
        }

        //create user
        $user = new UserModel();

        /**
         * do not copy password2 and rename password1 to just password when mapping processed
         * data to our model
        */
        $this->modelSkipField('password2')->modelRenameField('password1', 'password');
        $this->mapDataToModel($user)->save(); // it returns the model

        //set the new user id so that it can be accessed outside the class
        $this->setData('id', $user->id);

        return $this->succeeds();
    }
}
```

**The Controller**:

```php
/**
 * file auth controller
*/
namespace app\Controller;

use SomeNamespace\Request;
use SomeNamespace\JsonResponse;

use SomeNamespace\Controller as BaseController;
use app\Handler\AuthHandler;

class AuthController extends BaseController
{
    public function signup(Request $request, Response $response, AuthHandler $handler)
    {
        if ($handler->executeSignup())
            return $response([
                'status' => 'success',
                'data' => [
                    'userId' => $handler->id
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

> **Note that processed data can be accessed directly on the instance, thanks to php's magic `__get()` method. However, it throws `KeyNotFoundException` if you try accessing data whose key is not defined to help in troubleshooting**.

## Validation Rule Formats

Validation rules are defined as arrays keyed by the field names. Each field array can contain the following rule properties:

1. **type**: This indicates the type of validation to carry out on the field.

2. **required**: Boolean value that indicates if the field is required. defaults to true.

3. **default**: This property applies to non required field. It defaults to null.

4. **filters**: Defines array of filters to apply to the field value(s) prior to validations.

5. **check**: Array containing a database integrity check to run on the field value(s)

6. **checks**: Contains array of multiple database integrity checks to run on the field value(s).

7. **options**: Array containing every other validation rules

8. **requiredIf**: A conditional clause that makes the field mandatory if the clause is satisfied.

To reference a validation principal, the convention used is to enclose the principal field name in curly braces within a string . '{field-name}'. The module will find and resolve such, replacing it with the field value.

Other conventions include `{this}` which references the current field value under validation; `{_this}` references the current field name under validation while `{_index}` references the current field value index position (in the case of validating array of fields).

Finally, there is the `{CURRENT_DATE}`, `{CURRENT_YEAR}`, `{CURRENT_TIME}` that references the current date, current year and current timestamp values respectively.

```php
$rules = [
    'first-name' => [
        'type' => 'text',
        'options' => [
            'min' => 3,
            'minErr' => '{_this} should be at least 3 charaters length',
            //first-name should be at least 3 characters length
        ],
    ],
    //we are expecting an array of favorite colors
    'favorite-colors' => [
        'type' => 'choice',
        'filters' => [
            'toLower' => true, //convert the colors to lowercase
        ],
        'options' => [
            //choices to choose from
            'choices' => array('green', 'white', 'blue', 'red', 'violet', 'purple'),
            'err' => 'color {_index} is not a valid color',
            //color 1 is not a valid color'
        ],
    ],
    'subscribe-newsletter' => [
        'type' => 'boolean',
    ],
    //email is required if user checks the subscribe checkbox, else, do not require if
    'email' => [
        'type' => 'email',
        'requireIf' => [
            'condition' => 'checked',
            'field' => 'subscribe-newsletter'
        ]
        'err' => '{this} is not a valid email address'
    ],
]
```

## Validation Filters

Filters are applied to the field values prior to validations. You can use filters to modify field values prior to validations. The available filters include:

1. **decode**: Call php `urldecode()` function on the field value(s). Defaults to true

2. **trim**: Call php `trim()` function on the field value(s). Defaults to true

3. **stripTags**: Call php `strip_tags()` function on the field value(s). Defaults to true

4. **stripTagsIgnore**: Defines string of html tags that should not be stripped out if `stripTags` filter is set to true. defaults to empty string

5. **numeric**: Call php `floatval()` function on the field value(s). Defaults to false

6. **toUpper**: Call php `strtoupper()` function on the field value(s). Defaults to false

7. **toLower**: Call php `strtolower()` function on the field value(s). Defaults to false

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

- [Date Validation](#date-validation)

- [Range Validation](#range-validation)

- [Choice Validation](#choice-validation)

- [Numeric Validation](#numeric-validation)

- [Password Validation](#password-validation)

- [File Validation](#file-validation)

- [Image File Validation](#image-file-validation)

- [Audio File Validation](#audio-file-validation)

- [Video File Validation](#video-file-validation)

- [Media File Validation](#media-file-validation)

- [Document File Validation](#document-file-validation)

- [Archive File Validation](#archive-file-validation)

### Limiting Rule Validation

The limiting rule validation options touches every validation. It is where we can define the limiting length of a string, date or numeric values. These includes the **min**, **max**, **gt** (greater than) and **lt** (less than) options.

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

It is quite easy to carry out different flavours of regex rule tests on field value(s). There are four kinds of regex rules. These include single **regex** test, **regexAny**, **regexAll**, and **regexNone** tests.

For **regex** type, it must match the test, otherwise it is flagged as error. For **regexAny**, at least one of the tests must match. For **regexAll**, all regex tests must match. For **regexNone**, none of the regex tests should match.

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

### Date Validation

To validate dates, set the type property to *'date'*. You can specify [limiting rules](#limiting-rule-validation) that validates if the date is within a given limited range.

```php
$rules = [
    'date-of-birth' => [
        'type' => 'date',
        'options' => [
            'min' => '01-01-1990', //only interested in people born on or after 01-01-1990
            'max' => '{CURRENT_DATE}'
        ]
    ],
];
```

### Range Validation

To validate field as a range of value, set the type property to **range**. The range type accepts three more options keys, which are **from**, **to** and the optional **step** key that defaults to 1.

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

### Choice Validation

To validate field against a choice of options, set the type property to **choice**. Acceptable options are specified using the **choices** property as array. The [range](#range-validation) type makes use of this type validator internally.

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

### Email Validation

To validate email addresses, set the type property to `email`.

```php
$rules = [
    'email' => [
        'type' => 'email'
    ],
];
```

### URL Validation

To validate url, set the type property to `url`.

```php
$rules = [
    'website' => [
        'type' => 'url'
    ],
];
```

### Numeric Validation

To validate numeric values, whether floating or integers, there are nice validation types defined for such cases. These include the following types: **float** (**money** or **number**), **positiveFloat** or **pFloat**, **negativeFloat** or **nFloat**, **integer** or **int**, **positiveInteger** (**positiveInt**, **pInteger** or **pInt**), and **negativeInteger** (**negativeInt**, **nInteger** or **nInt**)

```php
$rules = [
    'favorite-number' => [
        'type' => 'number'
    ],
    'user-id' => [
        'type' => 'positiveInt',
    ]
];
```

### Password Validation

Password type validation is more like text validation except that some limiting rules and regex rules were added. The default validation implementation is that passwords must be at least 8 charaters long, and 28 characters max. It must contain at least two alphabets and at least two non-alphabets. You can override this default if you like.

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

### File Validation

The module can validate files, including the integrity of file mime types. It offers wide flavours of file validation types such as images, videos, audios, documents and archives.

File size units are recognised accurately that includes **bytes**, **kb**, **mb**, **gb** and **tb**.

```php
$rules => [
    'picture' => [
        'type' => 'file',
        'options' => [
            'min' => '50kb' //it will be converted accurately
        ]
    ],
];
```

You can define an absolute path to move the file to using the **moveTo** option. when the file is being, a hashed name is computed for it, and stored on the field such that it can be accessed using the `getData()` instance method on directly on the instance.

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

You can specify the accepted mime file extensions during validation. Note that the handler has a `FileExtensionDetector` module that detects file extension based on its first magic byte. Hence, limiting file extension spoofing errors. Please note that the current list of file magic bytes are still being updated, you can help us by reporting to us more magic bytes codes that are missing.

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

### Image Validation

The shortest way to validate image files is to use the `image` type option. The accepted mimes for images include **JPEG**, **PNG** and **GIF**.

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

### Audio File Validation

The easiest way to validate audio files is to use the `audio` type option. The accepted mimes for audios include **MP3** and others.

```php
$move_to = getcwd() . '/storage/media/audios';
$rules => [
    'pictures' => [
        'type' => 'audio',
        'options' => [
            'max' => '400mb',
            'moveTo' => $move_to,
        ],
    ],
];
```

### Video File Validation

The shortest way to validate video files is to use the `video` type option. The accepted mimes for videos include **MP4**, **OGG**, **MOVI**, and others.

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

### Media File Validation

The shortest way to validate media files (videos, images and audios) is to use the `media` type option. The accepted mimes is a combination of **video**, **image** and **audio** mimes.

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

### Document File Validation

The most convenient way to validate document files is to use the `document` type option. The accepted mimes for documents include **DOCX**, **PDF** and **DOC**, and others.

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

### Archive File Validation

The shortest way to validate archive files is to use the `archive` type option. The accepted mimes for archives include **ZIP**, **TAR.GZ** and **TAR**, and others.

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

### How To Implement the DBCheckerAbstract Interface

To enable database integrity checks, you must implement two methods on the `DBCheckerAbstract` class which are the `buildQuery()` and `execute()` methods. Then you have to supply an instance of your concrete class as the fourth argument to the `Handler`.

Below shows how you can do this in [Laravel](https://laravel.com):

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

Then we can define our own `BaseHandler` and supply an instance of our concrete class as fourth argument to the parent constructor as shown below:

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
                    'if' => 'exists', // note that it is error if it exists
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
        $this->setData('id', $user->id);

        return $this->succeeds();
    }
}
```

### Defining Check and Checks Integrity Rules

The `check` option defines a single database integrity check while the `checks` option defines array of database integrity checks.

While defining the rules, one can decide to write the select query to execute. In this case, there should be a `query` property and the `params` array property if needed (it defaults to empty array if not given).

The second alternative is to perform the check on a single entity field. In this case, there should be the `entity` property that references the database table to select from, and the `field` property that defines the table column to be used in the where clause. If the `field` property is omitted, It will default to `id` if the field value is an integer, otherwise, it default to the resolved field name (either camelized or snaked cased depending on the state of the **modelCamelizeFields($status)** method).

```php
$rules = [
    'userid' => [
        'type' => 'positiveInt',
        'check' => [
            'if' => 'notExist',
            'entity' => 'users'
            //since no query is defined, the field option will default to id rather than
            // userid because the field value is an integer,

            //params options will default to array(current_field_value)
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

### How to Write Your Custom Validation Types

The module is built to be extensible such that you can define more validation methods and use your own custom rule types. You would need to understand some basic things on how the module works. Insecting the `ValidatorInterface` and the `Validator` class files is a nice place to start. Below shows how this can be easily achieved.

**Define our custom validator that inherits from the main validator**:

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

Then we can define our own `BaseHandler` that integrates the newly added **name** type validator method like shown below:

```php
//file BaseHandler
namespace app\Handler;

use Forensic\Handler\Handler as ParentHandler;

class BaseHandler extends ParentHandler
{
    public function construct($source = null, array $rules = null)
    {
        parent::__construct($source, $rules, new CustomValidator(), new DBChecker());
    }

    /**
     *@override the parent method.
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

### The RequiredIf or RequireIf Option

With this option, we can make a field to be mandatory if a given condition is satisfied. Such conditions include:

1. If another field is checked or if it is not checked

    ```php
    $rules = [
        'is-current-work' => [
            'type' => 'boolean',
        ],
        'work-end-month' => [
            'type' => 'range',
            'options' => [
                'from' => 1,
                'to' => 12
            ],
            'requiredIf' => [
                'condition' => 'notChecked',
                'field' => 'is-current-work'
            ],
        ],
        'subscribe-newsletter' => [
            'type' => 'boolean'
        ],
        'email' => [
            'requiredIf' => [
                'condition' => 'checked',
                'field' => 'subscribe-newsletter'
            ],
        ],
    ];
    ```

2. If another field equals a given value or if it does not equal a given value

    ```php
    $rules = [
        'country' => [
            'type' => 'choice',
            'options' => [
                'choices' => array('ng', 'us', 'gb', 'ca', 'gh')
            ],
        ],
        //if your country is not nigeria, tell us your country calling code
        'calling-code' => [
            'requiredIf' => [
                'condition' => 'notEquals',
                'value' => 'ng',
                'field' => 'country'
            ],
        ],
        //if you are in nigeria, you must tell us your salary demand
        'salary-demand' => [
            'requiredIf' => [
                'condition' => 'equals',
                'value' => 'ng',
                'field' => 'country'
            ],
        ],
    ];
    ```