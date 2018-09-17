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
}