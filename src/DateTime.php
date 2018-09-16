<?php
/**
 * extend dateTime and provide php magic __toString method
*/
declare(strict_types = 1);
namespace Forensic\Handler;

use DateTimeZone;

class DateTime extends \DateTime
{
    private $_to_string_format = '';

    public function __construct(string $time = 'now', DateTimeZone $timezone = null,
        string $to_string_format = 'Y-m-d')
    {
        parent::__construct($time, $timezone);
        $this->setToStringFormat($to_string_format);
    }

    /**
     * sets the to string format used
    */
    public function setToStringFormat(string $to_string_format)
    {
        $this->_to_string_format = $to_string_format;
    }

    /**
     * gets the to string format used
     *@return string
    */
    public function getToStringFormat()
    {
        return $this->_to_string_format;
    }

    /**
     *@return string
    */
    public function __toString()
    {
        return $this->format($this->getToStringFormat());
    }
}