<?php
declare(strict_types = 1);

namespace Forensic\Handler\Interfaces;

interface ValidatorInterface
{
    public function setErrorBag(array &$error_bag);

    public function getErrorBag(): array;

    public function succeeds(): bool;

    public function fails(): bool;
}