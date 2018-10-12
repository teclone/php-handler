<?php
/**
 * The db checker module
*/
declare(strict_types = 1);
namespace Forensic\Handler;

use Forensic\Handler\Traits\Common;
use Forensic\Handler\Abstracts\DBCheckerAbstract;

class DBChecker extends DBCheckerAbstract
{
    /**
     * executes the query
    */
    protected function execute(string $query, array $params, array $options): array
    {
        $result = [];

        return $result;
    }

    /**
     * construct query from the given options
     *
     *@param array $options - array of options
    */
    protected function buildQuery(array $options): string
    {
        $query = Util::value('query', $options);
        if ($query === '')
            $query = 'SELECT 1 FROM ' . $options['entity'] . ' WHERE ' . $options['field'] . '=?';

        return $query;
    }
}