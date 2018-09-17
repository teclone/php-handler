<?php
declare(strict_types = 1);

namespace Forensic\Handler\Interfaces;

interface ValidatorInterface
{
    public function setErrorBag(array &$error_bag);

    public function getErrorBag(): array;

    public function succeeds(): bool;

    public function fails(): bool;

    /** text validation interface */
    public function validateText(bool $required, string $field, $value, array $options): bool;

    /** date validation interface */
    public function validateDate(bool $required, string $field, $value, array $options): bool;

    /** integer validation interface */
    public function validateInteger(bool $required, string $field, $value, array $options): bool;

    /** positive integer validation interface */
    public function validatePInteger(bool $required, string $field, $value, array $options): bool;

    /** negative integer validation interface */
    public function validateNInteger(bool $required, string $field, $value, array $options): bool;

    /** float validation interface */
    public function validateFloat(bool $required, string $field, $value, array $options): bool;

    /** positive float validation interface */
    public function validatePFloat(bool $required, string $field, $value, array $options): bool;

    /** negative float validation interface */
    public function validateNFloat(bool $required, string $field, $value, array $options): bool;

    /** email validation interface */
    public function validateEmail(bool $required, string $field, $value, array $options): bool;

    /** url validation interface */
    public function validateURL(bool $required, string $field, $value, array $options): bool;
}