<?php
declare(strict_types = 1);
/**
 * the validator module
 *
 * limiting rules options include min, max, gt (greaterThan), and lt (lessThan) options,
 *
 * Their associated errors are minErr, maxErr, gtErr, and ltErr.
 *
 * Their is the formats option, that is an array of regex expressions.
 * It is an error if the value did not match any of the entries.
 *
 * e.g 'formats' => [
 *      'tests' =>  ['array of regex expressions to test'],
 *      'err' => 'error message if none of the regex matches'
 * ]
 *
 * Their is the badFormats option, that is an array of regex expressions.
 * It is an error if the value matches at least one of the regex expressions.
 *
 * e.g 'badFormats' => [
 *      //array of format
 *      [
 *          'test' => '/regextotest/',
 *          'err' => 'error message is this bad test matches'
 *      ],
 *      //more test arrays
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
    public function validateText(bool $required, string $field, $value, array $options)
    {
        if ($this->reset($field, $options) && $this->shouldValidate($required, $field, $value))
        {
            //validate the limiting rules
            $len = strlen($value);
            $this->checkLimitingRules($value, $len, null, ' characters');
        }
        return $this->succeeds();
    }
}