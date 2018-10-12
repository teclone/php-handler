<?php
declare(strict_types = 1);

namespace Forensic\Handler\Interfaces;

interface ValidatorInterface extends CommonInterface
{
    public function setFileExtensionDetector(
        FileExtensionDetectorInterface $file_extension_detector
    );

    /** text validation interface */
    public function validateText(bool $required, string $field, $value,
        array $options, int $index = 0): bool;

    /** date validation interface */
    public function validateDate(bool $required, string $field, $value,
        array $options, int $index = 0): bool;

    /** integer validation interface */
    public function validateInteger(bool $required, string $field, $value,
        array $options, int $index = 0): bool;

    /** positive integer validation interface */
    public function validatePInteger(bool $required, string $field, $value,
        array $options, int $index = 0): bool;

    /** negative integer validation interface */
    public function validateNInteger(bool $required, string $field, $value,
        array $options, int $index = 0): bool;

    /** float validation interface */
    public function validateFloat(bool $required, string $field, $value,
        array $options, int $index = 0): bool;

    /** positive float validation interface */
    public function validatePFloat(bool $required, string $field, $value,
        array $options, int $index = 0): bool;

    /** negative float validation interface */
    public function validateNFloat(bool $required, string $field, $value,
        array $options, int $index = 0): bool;

    /** email validation interface */
    public function validateEmail(bool $required, string $field, $value,
        array $options, int $index = 0): bool;

    /** url validation interface */
    public function validateURL(bool $required, string $field, $value,
        array $options, int $index = 0): bool;

    /** choice validation interface */
    public function validateChoice(bool $required, string $field, $value,
        array $options, int $index = 0): bool;

    /** range validation interface */
    public function validateRange(bool $required, string $field, $value,
        array $options, int $index = 0): bool;

    /** password validation interface */
    public function validatePassword(bool $required, string $field, $value,
        array $options, int $index = 0): bool;

    /** file validation interface */
    public function validateFile(bool $required, string $field, $value,
        array $options, int $index = 0, string &$new_value = null): bool;
}