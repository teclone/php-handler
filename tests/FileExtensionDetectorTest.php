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
     * test the resolve extension method
    */
    public function testResolveExtension()
    {
        //test that it converts ext to lower case
        $this->assertEquals('png', $this->_file_ext_detector->resolveExtension('PNG'));

        //test that it returns jpg for jpeg extensions
        $this->assertEquals('jpg', $this->_file_ext_detector->resolveExtension('jpeg'));
    }

    /**
     * test the resolve extensions method
    */
    public function testResolveExtensions()
    {
        //test that it correctly resolves all extensions in the array
        $this->assertEquals([], $this->_file_ext_detector->resolveExtensions([]));

        //test that it returns jpg for jpeg extensions
        $this->assertEquals(
            ['jpg', 'png'],
            $this->_file_ext_detector->resolveExtensionS(['jpeg', 'PNG'])
        );
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

    /**
     * test that the getDocumentMimes works as expected
    */
    public function testGetDocumentMimesMethod()
    {
        $mimes = $this->_file_ext_detector->getDocumentMimes();
        $this->assertTrue(is_array($mimes));
        $this->assertArraySubset(array('pdf', 'doc', 'docx'), $mimes);
    }

    /**
     * test that the getImageMimes works as expected
    */
    public function testGetImageMimesMethod()
    {
        $mimes = $this->_file_ext_detector->getImageMimes();
        $this->assertTrue(is_array($mimes));
        $this->assertArraySubset(array('gif', 'jpg', 'png'), $mimes);
    }

    /**
     * test that the getAudioMimes works as expected
    */
    public function testGetAudioMimesMethod()
    {
        $mimes = $this->_file_ext_detector->getAudioMimes();
        $this->assertTrue(is_array($mimes));
        $this->assertArraySubset(array('mp3'), $mimes);
    }

    /**
     * test that the getVideoMimes works as expected
    */
    public function testGetVideoMimesMethod()
    {
        $mimes = $this->_file_ext_detector->getVideoMimes();
        $this->assertTrue(is_array($mimes));
        $this->assertArraySubset(array('movi', 'mp4', 'ogg', 'webm'), $mimes);
    }

    /**
     * test that the getMediaMimes works as expected
    */
    public function testGetMediaMimesMethod()
    {
        $mimes = $this->_file_ext_detector->getMediaMimes();
        $this->assertTrue(is_array($mimes));
        $this->assertArraySubset(array(
            'gif', 'jpg', 'png', 'mp3', 'movi', 'mp4', 'ogg', 'webm'
        ), $mimes);
    }
}