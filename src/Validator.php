<?php
declare(strict_types = 1);
/**
 * the validator module
 *
 * limiting rules options include min, max, gt (greaterThan), and lt (lessThan) options,
 *
 * Their associated errors are minErr, maxErr, gtErr, and ltErr.
 *
 * Their is the regex family options that include
 *
 * regex is array of containing test regex expression and associated err. The value must match
 * the regex test else, it is flagged as error
 *
 * e.g 'regex' => [
 *          'test' => '/regex to test/',
 *          'err' => 'error to set if value does not match regex test'
 *      ]
 *
 * regexAll, contains array of regex expressions that the value must match. The value
 * must match all the regex all expressions, else it is flagged as an error
 *
 * e.g 'regexAll' => [
 *      //array of regex expressions,
 *      [
 *          'test' => '/regex to test/',
 *          'err' => 'error message to set if the test fails'
 *      ],
 *      [
 *          'test' => '/another regex to test/',
 *          'err' => 'error message to set if the test fails'
 *      ],
 * ]
 *
 * regexAny contains array of regex expression tests which must be mathed at least for one
 * regex expression
 * It is an error if the value did not match any of the entries.
 *
 * e.g 'regexAny' => [
 *      'tests' =>  ['/regex test one/', '/regex test two/', .....],
 *      'err' => 'error message if none of the regex matches'
 * ]
 *
 * regexNone, that is an array of regex expressions.
 * It is an error if the value matches any of the regex expressions.
 *
 * 'regexNone' => [
 *      //array of regex expressions,
 *      [
 *          'test' => '/regex to test/',
 *          'err' => 'error message to set if the test succeeds'
 *      ],
 *      [
 *          'test' => '/another regex to test/',
 *          'err' => 'error message to set if the test succeeds'
 *      ],
 * ]
*/
namespace Forensic\Handler;

use Forensic\Handler\Interfaces\ValidatorInterface;
use Forensic\Handler\Interfaces\FileExtensionDetectorInterface;
use Forensic\Handler\Exceptions\DirectoryNotFoundException;
use Exception;
use Forensic\Handler\Exceptions\FileMoveException;
use Forensic\Handler\Traits\Common;

class Validator implements ValidatorInterface
{
    use Common;

    /**
     * file extension detector
    */
    private $_file_extension_detector = null;

    /**
     * check file upload error
     *
     *@return bool
    */
    protected function checkFileUploadError(int $error_code, string $value)
    {
        if ($error_code === UPLOAD_ERR_OK)
            return true;

        $error = '';
        switch($error_code)
        {
            case UPLOAD_ERR_INI_SIZE:
                $error = 'File size exceeds upload_max_filesize ini directive';
                break;

            case UPLOAD_ERR_FORM_SIZE:
                $error = 'File size exceeds max_file_size html form directive';
                break;

            case UPLOAD_ERR_NO_FILE:
                $error = 'No file upload found';
                break;

            case UPLOAD_ERR_NO_TMP_DIR:
                $error = 'No temp folder found for file storage';
                break;

            case UPLOAD_ERR_CANT_WRITE:
                $error = 'Permission denied while writing file to disk';
                break;

            case UPLOAD_ERR_EXTENSION:
                $error = 'Some loaded extensions aborted file processing';
                break;
            default:
                $error = 'Unknown file upload error';
                break;
        }
        return $this->setError($error, $value);
    }

    /**
     * validate match with
     *
     *@return bool
    */
    public function matchWith($value, array $options, string $prefix = '{_this}'): bool
    {
        if (array_key_exists('matchWith', $options) && $value != $options['matchWith'])
            $this->setError(
                Util::value('matchWithErr', $options, $prefix . ' did not match'),
                $value
            );

        return $this->succeeds();
    }

    /**
     * checks the regex none rules
     *
     *@param mixed $value - the value
     *@param array $regexes - array of regex expression arrays
    */
    protected function regexCheckNone($value, array $regexes)
    {
        if (count($regexes) === 0)
            return true;

        foreach($regexes as $regex)
        {
            if (!is_array($regex))
                continue; //skip if it is not an array

            $test = Util::value('test', $regex, null);
            if (!is_null($test) && preg_match($test, $value))
                return $this->setError(
                    Util::value('err', $regex, '{this} format not acceptable or contains invalid characters'),
                    $value
                );
        }
        return true;
    }

    /**
     * checks the regex any rules
     *
     *@param mixed $value - the value
     *@param array $regex - array of regex expressions tests
    */
    protected function regexCheckAny($value, array $regex)
    {
        $tests = Util::arrayValue('tests', $regex);

        if (count($tests) === 0)
            return true;

        foreach($tests as $test)
        {
            if (preg_match($test, $value))
                return true;
        }

        return $this->setError(
            Util::value('err', $regex, '{this} did not match any of the expected formats'),
            $value
        );
    }

    /**
     * checks the regex all rules
     *
     *@param mixed $value - the value
     *@param array $regexes - array of regex expression arrays
    */
    protected function regexCheckAll($value, array $regexes)
    {
        if (count($regexes) === 0)
            return true;

        foreach($regexes as $regex)
        {
            if (!is_array($regex))
                continue; //skip if it is not an array

            $test = Util::value('test', $regex, null);
            if (!is_null($test) && !preg_match($test, $value))
                return $this->setError(
                    Util::value('err', $regex, '{this} format not acceptable or contains invalid characters'),
                    $value
                );
        }
        return true;
    }

    /**
     * checks the regex rule
     *
     *@param mixed $value - the value
     *@param array $regex - array of regex test expression
    */
    protected function regexCheck($value, array $regex)
    {
        $test = Util::value('test', $regex, null);
        if (!is_null($test) && !preg_match($test, $value))
            return $this->setError(
                Util::value('err', $regex, '{this} is not a valid value'),
                $value
            );
        else
            return true;
    }

    /**
     * runs regex rule checks
     *
     *@param string $value - the field value
     *@param array $options, int $index = 0 - field rule options
    */
    protected function checkRegexRules(string $value, array $options, int $index = 0)
    {
        //check for regex rule
        if ($this->succeeds())
            $this->regexCheck($value, Util::arrayValue('regex', $options));

        //check for regexAll rule
        if ($this->succeeds())
            $this->regexCheckAll($value, Util::arrayValue('regexAll', $options));

        //check for regexAny rule
        if ($this->succeeds())
            $this->regexCheckAny($value, Util::arrayValue('regexAny', $options));

        //check for regexNone rule
        if ($this->succeeds())
            $this->regexCheckNone($value, Util::arrayValue('regexNone', $options));

        return $this->succeeds();
    }

    /**
     * runs the callback method on the given value
     *
     *@param mixed $value - the value
     *@param callable $callback - the callback method
     *@return mixed
    */
    protected function runCallback($value, callable $callback = null)
    {
        if (is_null($callback))
            return $value;

        return $callback($value);
    }

    /**
     * resolve limiting value. string values will be converted accurately
    */
    protected function resolveLimitingValue(string $key, array $options)
    {
        $value = Util::value($key, $options);
        if (is_string($value))
        {
            if (preg_match('/^(\d+[.]?\d*)(mb|kb|gb|tb)$/i', $value, $matches))
            {
                $number = floatval($matches[1]);
                switch(strolower($matches[2]))
                {
                    case 'kb':
                        return $number * 1000;
                    case 'mb':
                        return $number * 1000000;
                    case 'gb':
                        return $number * 1000000000;
                    case 'tb':
                        return $number * 1000000000000;
                }
            }
        }
        return $value;
    }

    /**
     * checks the limiting rules such as min, max, lt, gt
     *
     *@param mixed $value - the value
     *@param int|float|Datetime $actual - the actual value
     *@param array $options, int $index = 0 - the field rules
     *@param callback [$callback=null] - the callback method
     *@param string [$sufix] - a string sufix to use
     *@param string [$prefix] - a string prefix to use
     *@return bool
    */
    protected function checkLimitingRules($value, $actual, callable $callback = null,
        string $sufix = '', string $prefix = '{_this}'): bool
    {
        $options = $this->_options;
        //check the min limit
        $min = $this->resolveLimitingValue('min', $options);
        if (!is_null($min))
        {
            $min = $this->runCallback($min, $callback);
            if($actual < $min)
            {
                $default_err = $prefix . ' should not be less than ' . $min . $sufix;
                return $this->setError(Util::value('minErr',$options, $default_err), $value);
            }
        }

        //check the max limit
        $max = $this->resolveLimitingValue('max', $options);
        if (!is_null($max))
        {
            $max = $this->runCallback($max, $callback);
            if($actual > $max)
            {
                $default_err = $prefix . ' should not be greater than ' . $max . $sufix;
                return $this->setError(Util::value('maxErr',$options, $default_err), $value);
            }
        }

        //check the gt limit
        $gt = $this->resolveLimitingValue('gt', $options);
        if (!is_null($gt))
        {
            $gt = $this->runCallback($gt, $callback);
            if($actual <= $gt)
            {
                $default_err = $prefix . ' should be greater than ' . $gt . $sufix;
                return $this->setError(Util::value('gtErr',$options, $default_err), $value);
            }
        }

        //check the lt limit
        $lt = $this->resolveLimitingValue('lt', $options);
        if (!is_null($lt))
        {
            $lt = $this->runCallback($lt, $callback);
            if($actual >= $lt)
            {
                $default_err = $prefix . ' should be less than ' . $lt . $sufix;
                return $this->setError(Util::value('ltErr',$options, $default_err), $value);
            }
        }

        return $this->succeeds();
    }

    /**
     * runs post validation
    */
    protected function postValidate($value, array $options)
    {
        if ($this->succeeds())
            $this->matchWith($value, $options);

        return $this->succeeds();
    }

    /**
     * resets the validator, and checks if the validation should proceed
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
        else if (is_null($value) || $value === '')
        {
            $this->setError('{_this} is required', $value);
            $this->shouldProceed(false);
        }
        else
        {
            //cast to string
            $value = strval($value);
            $this->shouldProceed(true);
        }

        return $this->shouldProceed();
    }

    /**
     *@param array [$error_bag] - the error bag, passed by reference
    */
    public function __construct(array &$error_bag = [],
        FileExtensionDetectorInterface $file_extension_detector = null)
    {
        if (is_null($file_extension_detector))
            $file_extension_detector = new FileExtensionDetector;

        $this->_succeeds = false;
        $this->setErrorBag($error_bag);
        $this->setFileExtensionDetector($file_extension_detector);
    }

    /**
     * sets the file extension detector
    */
    public function setFileExtensionDetector(
        FileExtensionDetectorInterface $file_extension_detector)
    {
        $this->_file_extension_detector = $file_extension_detector;
    }

    /**
     * validates text
     *
     *@param bool $required - boolean indicating if field is required
     *@return bool
    */
    public function validateText(bool $required, string $field, $value,
        array $options, int $index = 0): bool
    {
        if ($this->setup($required, $field, $value, $options, $index))
        {
            //validate the limiting rules
            $len = strlen($value);
            $this->checkLimitingRules($value, $len, null, ' characters');

            //check for formatting rules
            if ($this->succeeds())
                $this->checkRegexRules($value, $options);
        }
        return $this->postValidate($value, $options);
    }

    /**
     * validates date
     *
     *@param bool $required - boolean indicating if field is required
     *@return bool
    */
    public function validateDate(bool $required, string $field, $value,
        array $options, int $index = 0): bool
    {
        if ($this->setup($required, $field, $value, $options, $index))
        {
            //check date format
            $format = '/^([0-9]{4})([-._:|\/\s])?([0-9]{1,2})\2?([0-9]{1,2})$/';
            $date = null;
            if (preg_match($format, $value, $matches))
            {
                $year = intval($matches[1]);
                $month = intval($matches[3]);
                $day = intval($matches[4]);

                //check date validity
                if (checkdate($month, $day, $year))
                {
                    $date_tokens = [$year, $month, $day];
                    $date = new DateTime(implode('-', $date_tokens));
                }
                else
                {
                    $this->setError(
                        Util::value('err', $options, '{this} is not a valid date'),
                        $value
                    );
                }
            }
            else
            {
                $this->setError(
                    Util::value('formatErr', $options, '{this} is not a valid date format'),
                    $value
                );
            }

            //validate the limiting rules
            if (!is_null($date))
                $this->checkLimitingRules($value, $date, function($value) {
                    return $value instanceof DateTime? $value : new DateTime($value);
                });
        }
        return $this->postValidate($value, $options);
    }

    /**
     * validates integers
     *
     *@param bool $required - boolean indicating if field is required
     *@return bool
    */
    public function validateInteger(bool $required, string $field, $value,
        array $options, int $index = 0): bool
    {
        if ($this->setup($required, $field, $value, $options, $index))
        {
            if (preg_match('/^[-+]?\d+$/', $value))
                $this->checkLimitingRules($value, intval($value));
            else
                $this->setError(
                    Util::value('err', $options, '{this} is not a valid integer'),
                    $value
                );
        }
        return $this->postValidate($value, $options);
    }

    /**
     * validates positive integers
     *
     *@param bool $required - boolean indicating if field is required
     *@return bool
    */
    public function validatePInteger(bool $required, string $field, $value, array $options,
        int $index = 0): bool
    {
        if ($this->setup($required, $field, $value, $options, $index))
        {
            if (preg_match('/^[+]?\d+$/', $value))
                $this->checkLimitingRules($value, intval($value)); //check limiting rules
            else
                $this->setError(
                    Util::value('err', $options, '{this} is not a valid positive integer'),
                    $value
                );
        }
        return $this->postValidate($value, $options);
    }

    /**
     * validates negative integers
     *
     *@param bool $required - boolean indicating if field is required
     *@return bool
    */
    public function validateNInteger(bool $required, string $field, $value,
        array $options, int $index = 0): bool
    {
        if ($this->setup($required, $field, $value, $options, $index))
        {
            if (preg_match('/^-\d+$/', $value))
                $this->checkLimitingRules($value, intval($value)); //check limiting rules
            else
                $this->setError(
                    Util::value('err', $options, '{this} is not a valid negative integer'),
                    $value
                );
        }
        return $this->postValidate($value, $options);
    }

    /**
     * validates floats
     *
     *@param bool $required - boolean indicating if field is required
     *@return bool
    */
    public function validateFloat(bool $required, string $field, $value,
        array $options, int $index = 0): bool
    {
        if ($this->setup($required, $field, $value, $options, $index))
        {
            if (preg_match('/^(?:[-+]?\d+(\.\d+)?|\.\d+)$/', $value))
                $this->checkLimitingRules($value, floatval($value)); //check limiting rules
            else
                $this->setError(
                    Util::value('err', $options, '{this} is not a valid number'),
                    $value
                );
        }
        return $this->postValidate($value, $options);
    }

    /**
     * validates positive floats
     *
     *@param bool $required - boolean indicating if field is required
     *@return bool
    */
    public function validatePFloat(bool $required, string $field, $value,
        array $options, int $index = 0): bool
    {
        if ($this->setup($required, $field, $value, $options, $index))
        {
            if (preg_match('/^(?:\+?\d+(\.\d+)?|\.\d+)$/', $value))
                $this->checkLimitingRules($value, floatval($value)); //check limiting rules
            else
                $this->setError(
                    Util::value('err', $options, '{this} is not a valid positive number'),
                    $value
                );
        }
        return $this->postValidate($value, $options);
    }

    /**
     * validates negative floats
     *
     *@param bool $required - boolean indicating if field is required
     *@return bool
    */
    public function validateNFloat(bool $required, string $field, $value,
        array $options, int $index = 0): bool
    {
        if ($this->setup($required, $field, $value, $options, $index))
        {
            if (preg_match('/^[-]\d+(\.\d+)?$/', $value))
                $this->checkLimitingRules($value, floatval($value)); //check limiting rules
            else
                $this->setError(
                    Util::value('err', $options, '{this} is not a valid negative number'),
                    $value
                );
        }
        return $this->postValidate($value, $options);
    }

    /**
     * validates email
     *
     *@return bool
    */
    public function validateEmail(bool $required, string $field, $value,
        array $options, int $index = 0): bool
    {
        if ($this->setup($required, $field, $value, $options, $index))
        {
            if (filter_var($value, FILTER_VALIDATE_EMAIL))
                $this->checkRegexRules($value, $options);
            else
                $this->setError(
                    Util::value('err', $options, '{this} is not a valid email address'),
                    $value
                );
        }
        return $this->postValidate($value, $options);
    }

    /**
     * validates url
     *
     *@param bool $required - boolean indicating if field is required
     *@return bool
    */
    public function validateURL(bool $required, string $field, $value,
        array $options, int $index = 0): bool
    {
        if ($this->setup($required, $field, $value, $options, $index))
        {
            $format = '/^'
                . '(?:(?:(https|http|ftp):\/\/))?' //match optional scheme
                . '([0-9a-z][-\w]*[0-9a-z]\.)+' //match domain name with ending dot
                . '([a-z]{2,9})' // match the domain prefix
                . '(?::\d{1,4})?' //match optional port
                . '([#?\/][-()_\w\/#~:.?+=&%@]*)?' //match any additonal paths, or query or hash
                . '$/i';

            if (preg_match($format, $value))
                $this->checkRegexRules($value, $options);
            else
                $this->setError(
                    Util::value('err', $options, '{this} is not a valid url'),
                    $value
                );
        }
        return $this->postValidate($value, $options);
    }

    /**
     * validates choice
     *
     *@param bool $required - boolean indicating if field is required
     *@return bool
    */
    public function validateChoice(bool $required, string $field, $value,
        array $options, int $index = 0): bool
    {
        $original_value = $value;
        if ($this->setup($required, $field, $value, $options, $index))
        {
            $choices = Util::arrayValue('choices', $options);
            if (!in_array($value, $choices) && !in_array($original_value, $choices))
                $this->setError(
                    Util::value('err', $options, '{this} is not an acceptable choice'),
                    $value
                );
        }
        return $this->postValidate($value, $options);
    }

    /**
     * validates range of options, either numbers or alphabets with optional step increment
    */
    public function validateRange(bool $required, string $field, $value,
        array $options, int $index = 0): bool
    {
        $from = Util::value('from', $options);
        $to = Util::value('to', $options);
        $step = Util::value('step', $options, 1); //default step of 1

        $options['choices'] = range($from, $to, abs($step));

        return $this->validateChoice($required, $field, $value, $options);
    }

    /**
     * validate password
    */
    public function validatePassword(bool $required, string $field, $value,
        array $options, int $index = 0): bool
    {
        $options['min'] = Util::value('min', $options, 8);
        $options['max'] = Util::value('max', $options, 28);

        $options['regexAll'] = Util::arrayValue('regexAll', $options, [
            //password should contain at least two alphabets
            [
                'test' => '/[a-z].*[a-z]/i',
                'err' => 'Password must contain at least two letter alphabets'
            ],
            //password should contain at least two non letter alphabets
            [
                'test' => '/[^a-z].*[^a-z]/i',
                'err' => 'Password must contain at least two non letter alphabets'
            ]
        ]);

        if ($this->setup($required, $field, $value, $options, $index))
        {
            //validate the limiting rules
            $len = strlen($value);
            $this->checkLimitingRules($value, $len, null, ' characters', 'Password');

            //check for regex rules
            if ($this->succeeds())
                $this->checkRegexRules($value, $options);

            //check for match with rule
            if ($this->succeeds())
                $this->matchWith($value, $options, 'Passwords');
        }

        return $this->postValidate($value, $options);
    }

    /**
     * validates file upload
     *
     *@throws DirectoryNotFoundException
     *@throws FileMoveException
    */
    public function validateFile(bool $required, string $field, $value,
    array $options, int $index = 0, string &$new_value = null): bool
    {
        if ($this->setup($required, $field, $value, $options, $index))
        {
            $files = $_FILES[$field];

            //validate file upload error
            $error_code = Util::makeArray($files['error'])[$index];
            if (!$this->checkFileUploadError($error_code, $value))
                return $this->postValidate($value, $options);

            //validate limiting rules
            $file_size = Util::makeArray($files['size'])[$index];
            if (!$this->checkLimitingRules($value, $file_size))
                return $this->postValidate($value, $options);

            //test file extension
            $magic_byte = '';
            $ext = '';

            $temp_filename = Util::makeArray($files['tmp_name'])[$index];
            $exts = $this->_file_extension_detector->detect($temp_filename, $magic_byte);

            //if the detected ext is txt, use it
            if (in_array('txt', $exts))
            {
                $ext = 'txt';
            }
            else if (preg_match('/\.(\w+)$/', $value, $matches))
            {
                /**if the file name contains ext set it as error if the extension is not
                 * our list of detected extensions
                */
                $ext = $this->_file_extension_detector->resolveExtension($matches[1]);
                if (!in_array($ext, $exts))
                    return $this->setError('File extension spoofing detected', $value);
            }
            else
            {
                $ext = $exts[0];
            }

            //validate mimes
            $mimes = $this->_file_extension_detector->resolveExtensions(
                Util::arrayValue('mimes', $options)
            );

            if(count($mimes) > 0 && !in_array($ext, $mimes))
            {
                return $this->setError(
                    Util::value('mimeErr', $options, '".' . $ext . '" file extension not accepted'),
                    $value
                );
            }

            $move_to = Util::value('moveTo', $options, '');
            $ext = Util::value('overrideMime', $options, $ext); // override file extension mime if given
            //move file to some other location if moveTo option is set
            if ($move_to !== '')
            {
                $move_to = preg_replace('/\/+$/', '', $move_to) . '/';
                if (!is_dir($move_to))
                    throw new DirectoryNotFoundException($move_to . ' does not exist');

                //compute a hash for the filename
                $filename = bin2hex(random_bytes(16)) . '.' . $ext;
                $move_to .= $filename;

                try
                {
                    //rename will throw error if write permission is denied
                    if(rename($temp_filename, $move_to))
                        $new_value = $filename;
                    else
                        throw new Exception('Error occured while moving uploaded file');
                }
                catch(Exception $ex)
                {
                    throw new FileMoveException(
                        Util::value('moveErr', $options, 'Error occured while moving uploaded file'),
                        0,
                        $ex
                    );
                }
            }
        }
        return $this->postValidate($value, $options);
    }

    /**
     * validates image file upload
     *
     *@throws DirectoryNotFoundException
     *@throws FileMoveException
    */
    public function validateImage(bool $required, string $field, $value,
    array $options, int $index = 0, string &$new_value = null): bool
    {
        $options['mimes'] = Util::arrayValue(
            'mimes',
            $options,
            $this->_file_extension_detector->getImageMimes()
        );
        return $this->validateFile($required, $field, $value, $options, $index, $new_value);
    }

    /**
     * validates audio file upload
     *
     *@throws DirectoryNotFoundException
     *@throws FileMoveException
    */
    public function validateAudio(bool $required, string $field, $value,
    array $options, int $index = 0, string &$new_value = null): bool
    {
        $options['mimes'] = Util::arrayValue(
            'mimes',
            $options,
            $this->_file_extension_detector->getAudioMimes()
        );
        return $this->validateFile($required, $field, $value, $options, $index, $new_value);
    }

    /**
     * validates video file upload
     *
     *@throws DirectoryNotFoundException
     *@throws FileMoveException
    */
    public function validateVideo(bool $required, string $field, $value,
    array $options, int $index = 0, string &$new_value = null): bool
    {
        $options['mimes'] = Util::arrayValue(
            'mimes',
            $options,
            $this->_file_extension_detector->getVideoMimes()
        );
        return $this->validateFile($required, $field, $value, $options, $index, $new_value);
    }

    /**
     * validates media file upload
     *
     *@throws DirectoryNotFoundException
     *@throws FileMoveException
    */
    public function validateMedia(bool $required, string $field, $value,
    array $options, int $index = 0, string &$new_value = null): bool
    {
        $options['mimes'] = Util::arrayValue(
            'mimes',
            $options,
            $this->_file_extension_detector->getMediaMimes()
        );
        return $this->validateFile($required, $field, $value, $options, $index, $new_value);
    }

    /**
     * validates document file upload
     *
     *@throws DirectoryNotFoundException
     *@throws FileMoveException
    */
    public function validateDocument(bool $required, string $field, $value,
        array $options, int $index = 0, string &$new_value = null): bool
    {
        $options['mimes'] = Util::arrayValue(
            'mimes',
            $options,
            $this->_file_extension_detector->getDocumentMimes()
        );
        return $this->validateFile($required, $field, $value, $options, $index, $new_value);
    }
}