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
