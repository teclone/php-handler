<?php
declare(strict_types = 1);

namespace Forensic\Handler\Test;

use PHPUnit\Framework\TestCase;
use Forensic\Handler\DateTime;
use Forensic\Handler\FileExtensionDetector;
use Forensic\Handler\Exceptions\FileNotFoundException;
use Forensic\Handler\Exceptions\FileReadException;

class FileExtensionDetectorTest extends TestCase
{
    private $_file_ext_detector = null;

    public function setUp()
    {
        $this->_file_ext_detector = new FileExtensionDetector;
    }

    public function detectorTestProvider()
    {
        return [
            'jpg image extension detection' => [
                'tests/Helpers/file1.jpg',
                array('jpg')
            ],
        ];
    }

    public function testConstruct()
    {
        $this->assertInstanceOf(FileExtensionDetector::class, $this->_file_ext_detector);
    }

    /**
     * test that the detect method correctly detects a given file extension
     *@dataProvider detectorTestProvider
    */
    public function testDetectMethod(string $filename, array $exts)
    {
        $magic_byte = '';
        $result = $this->_file_ext_detector->detect($filename, $magic_byte);

        $this->assertEquals($exts, $result);
    }

    /**
     * test that the detect method throws file not found exception error if file
     * is not found
    */
    public function testFileNotFoundException()
    {
        $this->expectException(FileNotFoundException::class);
        $this->_file_ext_detector->detect('tests/Helpers/file0.jpg');
    }

    /**
     * test that the detect method throws file not found exception error if file
     * is not found
    */
    public function testFileReadException()
    {
        $this->expectException(FileReadException::class);
        $this->_file_ext_detector->detect('/root/');
    }
}