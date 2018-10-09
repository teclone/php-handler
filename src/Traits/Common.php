<?php
declare(strict_types = 1);

namespace Forensic\Handler\Traits;

use Forensic\Handler\Util;

trait Common
{
    /**
     * array of error bag containing all errors detected since the instance creation
    */
    private $_error_bag = [];

    /**
     * boolean property indicating if the last check succeeded
    */
    private $_succeeds = null;

    /**
     * current field under check
    */
    private $_field = null;

    /**
     * current field check options
    */
    private $_options = null;

    /**
     * the current field value index
    */
    private $_index = null;

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

                case '_index':
                    return $this->_index + 1;
            }
        }, $err);
        $this->_succeeds = false;
        return false;
    }

    /**
     * resets the validator
     *
     *@param string $field - the next field to validate
     *@param array $options - array of validation options
     *@param int [$index=0] - current field value index
     *@return true
    */
    protected function reset(string $field, array $options, int $index = 0)
    {
        $this->_field = $field;
        $this->_options = $options;
        $this->_index = $index;

        $this->_succeeds = true;
        return true;
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
     * returns error message for the given field, else returns null
     *
     *@return string|null
    */
    public function getError(string $field = null)
    {
        if (count($this->_error_bag) > 0)
        {
            if (!is_null($field) && array_key_exists($field, $this->_error_bag))
                return $this->_error_bag[$field];

            else if (is_null($field))
                return current($this->_error_bag);
        }
        return null;
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