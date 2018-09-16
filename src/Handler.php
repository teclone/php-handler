<?php
/**
 * Request validator
*/
declare(strict_types = 1);
namespace Forensic\Handler;

use Forensic\Handler\Exceptions\DataNotFoundException;
use Forensic\Handler\Exceptions\RuleNotFoundException;
use Forensic\Handler\Interfaces\ValidatorInterface;
use Forensic\Handler\Exceptions\DataSourceNotRecognizedException;

ini_set('filter.default', 'full_special_chars');
ini_set('filter.default_flags', '0');

class Handler
{
    /**
     * the raw data to be handled and processed
    */
    private $_source = [];

    /**
     * array of rules to apply
    */
    private $_rules = [];

    /**
     * the validator instance
    */
    private $_validator = null;

    /**
     *@param string|array $source - the data source
     *@param array $rules - rules to be applied on data
    */
    public function __construct($source = null, array $rules = null,
        ValidatorInterface $validator = null)
    {
        if (is_null($validator))
            $validator = new Validator();

        $this->_executed = false;
        $this->setSource($source);
        $this->setRules($rules);
        $this->setValidator($validator);
    }

    /**
     * sets the data source
     *
     *@param string|array $source - the data source
     *@return self
    */
    public function setSource($source = null)
    {
        if (is_string($source))
        {
            $source = strtolower($source);
            switch($source)
            {
                case 'get':
                    $this->_source = &$_GET;
                    break;
                case 'post':
                    $this->_source = &$_POST;
                    break;
                default:
                    $err = $source . ' is not a recognized data source';
                    throw new DataSourceNotRecognizedException($err);
            }
        }

        else if (is_array($source))
        {
            $this->_source = $source;
        }
        return $this;
    }

    /**
     * sets the rules
     *
     *@param array $rules - array of rules
     *@return self
    */
    public function setRules(array $rules = null)
    {
        if (is_array($rules))
        {
            $this->_rules = $rules;
        }
        return $this;
    }

    /**
     * sets the validator
     *
     *@param ValidatorInterface $validator - the validator
    */
    public function setValidator(ValidatorInterface $validator)
    {
        $this->_validator = $validator;
        $this->_validator->setErrorBag($this->_errors);
    }
}