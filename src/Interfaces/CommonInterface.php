<?php
declare(strict_types = 1);

namespace Forensic\Handler\Interfaces;

interface CommonInterface
{
    /* sets error bag */
    public function setErrorBag(array &$error_bag);

    /* returns the error bag */
    public function getErrorBag(): array;

    /* return the error string for the given string, else return null */
    public function getError(string $field);

    /* returns true if action succeeded */
    public function succeeds(): bool;

    /* returns true if action failed */
    public function fails(): bool;
}