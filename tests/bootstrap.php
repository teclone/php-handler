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