<?php
require 'vendor/autoload.php';

/**
 * returns a test file details located in the test Helpers directory
 *
 *@return array
*/
function getTestFileDetails(string $filename, string $type ,
    int $err_code = UPLOAD_ERR_OK)
{
    return [
        'name' => $filename,
        'tmp_name' => getcwd() . '/tests/Helpers/' . $filename,
        'size' => filesize('tests/Helpers/' . $filename),
        'type' => $type,
        'error' => $err_code,
    ];
}

/**
 * returns a test multi file details for files located in the test Helpers directory
 *
 *@return array
*/
function getTestMultiFileDetails(array $filenames, array $mimes, array $err_codes)
{
    return [
        'name' => $filenames,

        'tmp_name' => array_map(function($filename) {
            return getcwd() . '/tests/Helpers/' . $filename;
        }, $filenames),

        'size' => array_map(function($filename) {
            filesize('tests/Helpers/' . $filename);
        }, $filenames),

        'type' => $mimes,

        'error' => $err_codes,
    ];
}