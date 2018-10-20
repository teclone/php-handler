# Handler

Forensic Handler is a php module that sits independently between the controller and the model, performing request data validation, serialization and integrity checks. It is easy to setup and independent of any php framework and ORMs.

It makes the validation process easy and requires you to just define the data validation rules which are just php arrays.

The most interesting part is how easy it is to validate array of field data and files and the wide range of validation rule types that it affords you. It is also extensible so that you can define more validation rules if the need be.

Regarding database integrity checks, it is extensible enough to leave the db check implementation up to you by defining an abstract DBChecker class. This makes it not tied to any framework or ORM. It is quite easy to implement.

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

Validation rules are defined as arrays keyed by the their field names. Each field array should have a `type` property that defines the type of validation. Optional fields should have a `required` key property set to `false`. Every other rule details must go into the `options` array key except the `filters`, `matchWith` `check` and `checks` database rules.

During validation, there are some special ways of referencing the validation principals. `{_this}` references the current field under validation, `{this}` references the current field value under validation, while `{_index}` references the current field value index position (in the case of validating array of fields).

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

## Validation Rule Types

The module defines lots of validation rule types that covers a wide range of validation requirements. These includes the following:

- [Limiting Rule Validation](#limiting-rule-validation)

- [Regex Rule Validation](#regex-rule-validation)

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