<?php
declare(strict_types = 1);

namespace Forensic\Handler\Abstracts;

use Forensic\Handler\Traits\Common;
use Forensic\Handler\Interfaces\DBCheckerInterface;
use Forensic\Handler\Util;

abstract class DBCheckerAbstract implements DBCheckerInterface
{
    use Common;

    private $_query = '';

    /**
     * builds query from the given options. the options array contains
     * the following keys, => params, entity, field, and query. query is optional as well
     * as entity and field. params is an array of bind parameters,
    */
    abstract protected function buildQuery(array $options): string;

    /**
     * executes the select query
     *
     * it should return array of result or empty array if no result
    */
    abstract protected function execute(string $query, array $params, array $options): array;

    /**
     * calls the execute method with the appropriate parameters
     *
     *@return array
    */
    protected function runExecution(): array
    {
        return $this->execute($this->_query, $this->_options['params'], $this->_options);
    }

    /**
     * resolve parameter
     *
     *@param mixed $param - the parameter
     *@param mixed $value - the current field value
    */
    protected function resolveParam($param, $value)
    {
        return preg_replace_callback('/\{\s*([^}]+)\s*\}/', function($matches) use ($value) {
            $resolved = $matches[0];

            $capture = $matches[1];
            switch(strtolower($capture))
            {
                case 'this':
                    $resolved = $value;
                    break;
                case '_index':
                    $resolved = $this->_index;
                    break;
            }
            return $resolved;
        }, $param);
    }

    /**
     * resolve parameters
     *
     *@param array $params - array of parameters
     *@param mixed $value - the current value
     *@return array
    */
    protected function resolveParams(array $params, $value): array
    {
        return array_map(function($param) use ($value) {
            return $this->resolveParam($param, $value);
        }, $params);
    }

    /**
     * resets the db checker, and checks if the check call should proceed
     *
     *@return bool
    */
    protected function setup(bool $required, string $field, &$value,
        array $options, int $index = 0)
    {
        $this->reset($field, $options, $index);

        if (!$required && (is_null($value) || $value === ''))
        {
            $this->shouldProceed(false);
        }
        else
        {
            //resolve the params in the options array
            $this->_options['params'] = $this->resolveParams(
                Util::arrayValue('params', $this->_options),
                $value
            );
            //resolve the query in the options array
            $this->_options['query'] = $this->resolveParam(
                Util::value('query', $this->_options, ''),
                $value
            );
            //build the query if query is empty string
            $this->_query = $this->buildQuery($this->_options);

            $this->shouldProceed(true);
        }

        return $this->shouldProceed();
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
        if ($this->setup($required, $field, $value, $options, $index))
        {
            $result = $this->runExecution();
            //@codeCoverageIgnoreStart
            if (count($result) > 0)
                $this->setError(
                    Util::value('err', $options, 'the given {_this}: {this} does not exist'),
                    $value
                );
            //@codeCoverageIgnoreEnd
        }
        return $this->succeeds();
    }

    /**
     * check if a field does not exist, set error if it does not
    */
    public function checkIfNotExists(bool $required, string $field, $value,
        array $options, int $index = 0): bool
    {
        if ($this->setup($required, $field, $value, $options, $index))
        {
            $result = $this->runExecution();
            if (count($result) === 0)
                $this->setError(
                    Util::value('err', $options, 'the given {_this}: {this} does not exist'),
                    $value
                );
        }
        return $this->succeeds();
    }
}