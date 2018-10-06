<?php
/**
 * The db checker module
*/
declare(strict_types = 1);
namespace Forensic\Handler;

use Forensic\Handler\Interfaces\DBCheckerInterface;
use Forensic\Handler\Traits\Common;

class DBChecker implements DBCheckerInterface
{
    use Common;

    /**
     * returns boolean indicating if checked should proceed
     *
     *@param bool $required - boolean indicating if field is required
     *@param string $field - the field to check
     *@param mixed $value - the field value
     *@return bool
    */
    protected function shouldCheck(bool $required, string $field, $value)
    {
        //if the value is null, or empty and the field is not required, return false
        if (!$required && (is_null($value) || $value === ''))
            return false;

        else
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
     * check if a field exists, set error if it does
    */
    public function checkIfExists(bool $required, string $field, $value,
        array $options, int $index = 0): bool
    {
        if ($this->reset($field, $options, $index) &&
            $this->shouldCheck($required, $field, $value))
        {

        }
        return $this->succeeds();
    }

    /**
     * check if a field does not exist, set error if it does not
    */
    public function checkIfNotExists(bool $required, string $field, $value,
        array $options, int $index = 0): bool
    {
        if ($this->reset($field, $options, $index) &&
            $this->shouldCheck($required, $field, $value))
        {

        }
        return $this->succeeds();
    }
}