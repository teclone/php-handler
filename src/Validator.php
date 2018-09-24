<?php
declare(strict_types = 1);
/**
 * the validator module
 *
 * limiting rules options include min, max, gt (greaterThan), and lt (lessThan) options,
 *
 * Their associated errors are minErr, maxErr, gtErr, and ltErr.
 *
 * Their is the regex family options that include
 *
 * regexAll, contains array of regex expressions that the value must match. The value
 * must match all the regex all expressions, else it is flagged as an error
 *
 * e.g 'regexAll' => [
 *      //array of regex expressions,
 *      [
 *          'test' => '/regex to test/',
 *          'err' => 'error message to set if the test fails'
 *      ],
 *      [
 *          'test' => '/another regex to test/',
 *          'err' => 'error message to set if the test fails'
 *      ],
 * ]
 *
 * regexAny contains array of regex expression tests which must be mathed at least for one
 * regex expression
 * It is an error if the value did not match any of the entries.
 *
 * e.g 'regexAny' => [
 *      'tests' =>  ['/regex test one/', '/regex test two/', .....],
 *      'err' => 'error message if none of the regex matches'
 * ]
 *
 * regexNone, that is an array of regex expressions.
 * It is an error if the value matches any of the regex expressions.
 *
 * 'regexNone' => [
 *      //array of regex expressions,
 *      [
 *          'test' => '/regex to test/',
 *          'err' => 'error message to set if the test succeeds'
 *      ],
 *      [
 *          'test' => '/another regex to test/',
 *          'err' => 'error message to set if the test succeeds'
 *      ],
 * ]
*/
namespace Forensic\Handler;

use Forensic\Handler\Interfaces\ValidatorInterface;

class Validator implements ValidatorInterface
{
    /**
     * array of error bag containing all errors detected since the instance creation
    */
    private $_error_bag = [];

    /**
     * boolean property indicating if the last validation succeeded
    */
    private $_succeeds = null;

    /**
     * current field under validation
    */
    private $_field = null;

    /**
     * current field rule options
    */
    private $_options = null;

    /**
     * sets error error message
     *
     *@param string $err - the error message
     *@param mixed $value - the value
    */
    protected function setError(string $err, $value)
    {
        if (!Util::isNumeric($value))
            $value = '"' . $value . '"';

        $this->_error_bag[$this->_field] = preg_replace_callback(
            '/\{([^}]+)\}/', function($matches) use ($value) {
            $capture = $matches[1];

            switch(strtolower($capture))
            {
                case 'this':
                    return $value;

                case '_this':
                    return $this->_field;
            }
        }, $err);
        $this->_succeeds = false;
        return false;
    }

    /**
     * checks the regex none rules
     *
     *@param mixed $value - the value
     *@param array $regexes - array of regex expression arrays
    */
    protected function regexCheckNone($value, array $regexes)
    {
        if (count($regexes) === 0)
            return true;

        foreach($regexes as $regex)
        {
            if (!is_array($regex))
                continue; //skip if it is not an array

            $test = Util::value('test', $regex, null);
            if (!is_null($test) && preg_match($test, $value))
                return $this->setError(
                    Util::value('err', $regex, '{this} format not acceptable or contains invalid characters'),
                    $value
                );
        }
        return true;
    }

    /**
     * checks the regex any rules
     *
     *@param mixed $value - the value
     *@param array $regex - array of regex expressions tests
    */
    protected function regexCheckAny($value, array $regex)
    {
        $tests = Util::arrayValue('tests', $regex);

        if (count($tests) === 0)
            return true;

        foreach($tests as $test)
        {
            if (preg_match($test, $value))
                return true;
        }

        return $this->setError(
            Util::value('err', $regex, '{this} did not match any of the expected formats'),
            $value
        );
    }

    /**
     * checks the regex all rules
     *
     *@param mixed $value - the value
     *@param array $regexes - array of regex expression arrays
    */
    protected function regexCheckAll($value, array $regexes)
    {
        if (count($regexes) === 0)
            return true;

        foreach($regexes as $regex)
        {
            if (!is_array($regex))
                continue; //skip if it is not an array

            $test = Util::value('test', $regex, null);
            if (!is_null($test) && !preg_match($test, $value))
                return $this->setError(
                    Util::value('err', $regex, '{this} format not acceptable or contains invalid characters'),
                    $value
                );
        }
        return true;
    }

    /**
     * runs regex rule checks
     *
     *@param string $value - the field value
     *@param array $options - field rule options
    */
    protected function checkRegexRules(string $value, array $options)
    {
        //check for regexAll rule
        if ($this->succeeds())
            $this->regexCheckAll($value, Util::arrayValue('regexAll', $options));

        //check for regexAny rule
        if ($this->succeeds())
            $this->regexCheckAny($value, Util::arrayValue('regexAny', $options));

        //check for regexNone rule
        if ($this->succeeds())
            $this->regexCheckNone($value, Util::arrayValue('regexNone', $options));
    }

    /**
     * runs the callback method on the given value
     *
     *@param mixed $value - the value
     *@param callable $callback - the callback method
     *@return mixed
    */
    protected function runCallback($value, callable $callback = null)
    {
        if (is_null($callback))
            return $value;

        return $callback($value);
    }

    /**
     * checks the limiting rules such as min, max, lt, gt
     *
     *@param mixed $value - the value
     *@param int|float|Datetime $actual - the actual value
     *@param array $options - the field rules
     *@param callback [$callback=null] - the callback method
    */
    protected function checkLimitingRules($value, $actual, callable $callback = null,
        string $suffix = '')
    {
        $options = $this->_options;
        //check the min limit
        $min = Util::value('min', $options);
        if (!is_null($min))
        {
            $min = $this->runCallback($min, $callback);
            if($actual < $min)
            {
                $default_err = '{_this} should not be less than ' . $min . $suffix;
                return $this->setError(Util::value('minErr',$options, $default_err), $value);
            }
        }

        //check the max limit
        $max = Util::value('max', $options);
        if (!is_null($max))
        {
            $max = $this->runCallback($max, $callback);
            if($actual > $max)
            {
                $default_err = '{_this} should not be greater than ' . $max . $suffix;
                return $this->setError(Util::value('maxErr',$options, $default_err), $value);
            }
        }

        //check the gt limit
        $gt = Util::value('gt', $options);
        if (!is_null($gt))
        {
            $gt = $this->runCallback($gt, $callback);
            if($actual <= $gt)
            {
                $default_err = '{_this} should be greater than ' . $gt . $suffix;
                return $this->setError(Util::value('gtErr',$options, $default_err), $value);
            }
        }

        //check the lt limit
        $lt = Util::value('lt', $options);
        if (!is_null($lt))
        {
            $lt = $this->runCallback($lt, $callback);
            if($actual >= $lt)
            {
                $default_err = '{_this} should be less than ' . $lt . $suffix;
                return $this->setError(Util::value('ltErr',$options, $default_err), $value);
            }
        }
    }

    /**
     * returns boolean indicating if validation should proceed
     *
     *@param bool $required - boolean indicating if field is required
     *@param string $field - the field to validate
     *@param mixed $value - the field value
     *@return bool
    */
    protected function shouldValidate(bool $required, string $field, &$value)
    {
        if (!$required && (is_null($value) || $value === ''))
            return false;

        if (is_null($value) || $value === '')
        {
            $this->setError('{_this} field is required', $value);
            return false;
        }

        //cast to string
        $value = strval($value);
        return true;
    }

    /**
     * resets the validator
     *
     *@param string $field - the next field to validate
     *@param array $options - array of validation options
     *@return true
    */
    protected function reset(string $field, array $options)
    {
        $this->_field = $field;
        $this->_options = $options;

        $this->_succeeds = true;
        return true;
    }

    /**
     *@param array [$error_bag] - the error bag, passed by reference
    */
    public function __construct(array &$error_bag = [])
    {
        $this->_succeeds = false;
        $this->setErrorBag($error_bag);
    }

    /**
     * sets the validator error bag
     *
     *@param array $error_bag - the error bag, passed by reference
    */
    public function setErrorBag(array &$error_bag)
    {
        $this->_error_bag = &$error_bag;
    }

    /**
     * returns the error bag
     *
     *@return array
    */
    public function getErrorBag(): array
    {
        return $this->_error_bag;
    }

    /**
     * returns a boolean value indicating if the last validation call succeeded
     *
     *@return bool
    */
    public function succeeds(): bool
    {
        return $this->_succeeds;
    }

    /**
     * returns a boolean value indicating if the last validation call failed
     *
     *@return bool
    */
    public function fails(): bool
    {
        return !$this->succeeds();
    }

    /**
     * validates text
     *
     *@param bool $required - boolean indicating if field is required
     *@return bool
    */
    public function validateText(bool $required, string $field, $value, array $options): bool
    {
        if ($this->reset($field, $options) && $this->shouldValidate($required, $field, $value))
        {
            //validate the limiting rules
            $len = strlen($value);
            $this->checkLimitingRules($value, $len, null, ' characters');

            //check for formatting rules
            $this->checkRegexRules($value, $options);
        }
        return $this->succeeds();
    }

    /**
     * validates date
     *
     *@param bool $required - boolean indicating if field is required
     *@return bool
    */
    public function validateDate(bool $required, string $field, $value, array $options): bool
    {
        if ($this->reset($field, $options) && $this->shouldValidate($required, $field, $value))
        {
            //check date format
            $format = '/^([0-9]{4})([-._:|\/\s])?([0-9]{1,2})\2?([0-9]{1,2})$/';
            $date = null;
            if (preg_match($format, $value, $matches))
            {
                $year = intval($matches[1]);
                $month = intval($matches[3]);
                $day = intval($matches[4]);

                //check date validity
                if (checkdate($month, $day, $year))
                {
                    $date_tokens = [$year, $month, $day];
                    $date = new DateTime(implode('-', $date_tokens));
                }
                else
                {
                    $this->setError(
                        Util::value('err', $options, '{this} is not a valid date'),
                        $value
                    );
                }
            }
            else
            {
                $this->setError(
                    Util::value('formatErr', $options, '{this} is not a valid date format'),
                    $value
                );
            }

            //validate the limiting rules
            if (!is_null($date))
                $this->checkLimitingRules($value, $date, function($value) {
                    return $value instanceof DateTime? $value : new DateTime($value);
                });
        }
        return $this->succeeds();
    }

    /**
     * validates integers
     *
     *@param bool $required - boolean indicating if field is required
     *@return bool
    */
    public function validateInteger(bool $required, string $field, $value, array $options): bool
    {
        if ($this->reset($field, $options) && $this->shouldValidate($required, $field, $value))
        {
            if (preg_match('/^[-+]?\d+$/', $value))
                $this->checkLimitingRules($value, intval($value));
            else
                $this->setError(
                    Util::value('err', $options, '{this} is not a valid integer'),
                    $value
                );
        }
        return $this->succeeds();
    }

    /**
     * validates positive integers
     *
     *@param bool $required - boolean indicating if field is required
     *@return bool
    */
    public function validatePInteger(bool $required, string $field, $value, array $options): bool
    {
        if ($this->reset($field, $options) && $this->shouldValidate($required, $field, $value))
        {
            if (preg_match('/^[+]?\d+$/', $value))
                $this->checkLimitingRules($value, intval($value)); //check limiting rules
            else
                $this->setError(
                    Util::value('err', $options, '{this} is not a valid positive integer'),
                    $value
                );
        }
        return $this->succeeds();
    }

    /**
     * validates negative integers
     *
     *@param bool $required - boolean indicating if field is required
     *@return bool
    */
    public function validateNInteger(bool $required, string $field, $value, array $options): bool
    {
        if ($this->reset($field, $options) && $this->shouldValidate($required, $field, $value))
        {
            if (preg_match('/^-\d+$/', $value))
                $this->checkLimitingRules($value, intval($value)); //check limiting rules
            else
                $this->setError(
                    Util::value('err', $options, '{this} is not a valid negative integer'),
                    $value
                );
        }
        return $this->succeeds();
    }

    /**
     * validates floats
     *
     *@param bool $required - boolean indicating if field is required
     *@return bool
    */
    public function validateFloat(bool $required, string $field, $value, array $options): bool
    {
        if ($this->reset($field, $options) && $this->shouldValidate($required, $field, $value))
        {
            if (preg_match('/^(?:[-+]?\d+(\.\d+)?|\.\d+)$/', $value))
                $this->checkLimitingRules($value, floatval($value)); //check limiting rules
            else
                $this->setError(
                    Util::value('err', $options, '{this} is not a valid number'),
                    $value
                );
        }
        return $this->succeeds();
    }

    /**
     * validates positive floats
     *
     *@param bool $required - boolean indicating if field is required
     *@return bool
    */
    public function validatePFloat(bool $required, string $field, $value, array $options): bool
    {
        if ($this->reset($field, $options) && $this->shouldValidate($required, $field, $value))
        {
            if (preg_match('/^(?:\+?\d+(\.\d+)?|\.\d+)$/', $value))
                $this->checkLimitingRules($value, floatval($value)); //check limiting rules
            else
                $this->setError(
                    Util::value('err', $options, '{this} is not a valid positive number'),
                    $value
                );
        }
        return $this->succeeds();
    }

    /**
     * validates negative floats
     *
     *@param bool $required - boolean indicating if field is required
     *@return bool
    */
    public function validateNFloat(bool $required, string $field, $value, array $options): bool
    {
        if ($this->reset($field, $options) && $this->shouldValidate($required, $field, $value))
        {
            if (preg_match('/^[-]\d+(\.\d+)?$/', $value))
                $this->checkLimitingRules($value, floatval($value)); //check limiting rules
            else
                $this->setError(
                    Util::value('err', $options, '{this} is not a valid negative number'),
                    $value
                );
        }
        return $this->succeeds();
    }

    /**
     * validates email
     *
     *@return bool
    */
    public function validateEmail(bool $required, string $field, $value, array $options): bool
    {
        if ($this->reset($field, $options) && $this->shouldValidate($required, $field, $value))
        {
            if (filter_var($value, FILTER_VALIDATE_EMAIL))
                $this->checkRegexRules($value, $options);
            else
                $this->setError(
                    Util::value('err', $options, '{this} is not a valid email address'),
                    $value
                );
        }
        return $this->succeeds();
    }

    /**
     * validates url
     *
     *@param bool $required - boolean indicating if field is required
     *@return bool
    */
    public function validateURL(bool $required, string $field, $value, array $options): bool
    {
        if ($this->reset($field, $options) && $this->shouldValidate($required, $field, $value))
        {
            $format = '/^'
                . '(?:(?:(https|http|ftp):\/\/))?' //match optional scheme
                . '([0-9a-z][-\w]*[0-9a-z]\.)+' //match domain name with ending dot
                . '([a-z]{2,9})' // match the domain prefix
                . '(?::\d{1,4})?' //match optional port
                . '([#?\/][-()_\w\/#~:.?+=&%@]*)?' //match any additonal paths, or query or hash
                . '$/i';

            if (preg_match($format, $value))
                $this->checkRegexRules($value, $options);
            else
                $this->setError(
                    Util::value('err', $options, '{this} is not a valid url'),
                    $value
                );
        }
        return $this->succeeds();
    }

    /**
     * validates choice
     *
     *@param bool $required - boolean indicating if field is required
     *@return bool
    */
    public function validateChoice(bool $required, string $field, $value, array $options): bool
    {
        $original_value = $value;
        if ($this->reset($field, $options) && $this->shouldValidate($required, $field, $value))
        {
            $choices = Util::arrayValue('choices', $options);
            if (!in_array($value, $choices) && !in_array($original_value, $choices))
                $this->setError(
                    Util::value('err', $options, '{this} is not an accepted choice'),
                    $value
                );
        }
        return $this->succeeds();
    }
}