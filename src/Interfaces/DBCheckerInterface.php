<?php
declare(strict_types = 1);

namespace Forensic\Handler\Interfaces;

interface DBCheckerInterface extends CommonInterface
{
    /* check if a field value exists, set error if it exists */
    public function checkIfExists(bool $required, string $field, $value,
        array $options, int $index = 0): bool;

    /* check if a field value does not exist, set error if it does not exist */
    public function checkIfNotExists(bool $required, string $field, $value,
        array $options, int $index = 0): bool;
}