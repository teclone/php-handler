<?php
declare(strict_types = 1);

namespace Forensic\Handler;

use Forensic\Handler\Interfaces\FileExtensionDetectorInterface;
use Forensic\Handler\Exceptions\FileNotFoundException;
use Forensic\Handler\Exceptions\FileReadException;
use Exception;

/**
 * detects possible file extension based on the file's magic number
*/
class FileExtensionDetector implements FileExtensionDetectorInterface
{
    //file magic numbers
    private $_extensions = [
        //jpeg
        'ffd8ff' => array('jpg'),
        //png
        '89504e' => array('png'),
        //mp3
        'fffbb0' => array('mp3'),
        //mp4
        '000000' => array('mp4'),
        //xml, xslt, xsl
        'efbbbf' => array('xml', 'xslt', 'xsl'),
        //docx, zip
        '504b34' => array('docx', 'zip', 'xlsx', 'pptx'),
        //gz, tar.gz
        '1f8b80' => array('gz', 'tar.gz'),
        //doc, xls, ppt, msg, msi
        'd0cf11' => array('doc', 'xls', 'ppt', 'msg', 'msi', 'vsd'),
        //pdf
        '255044' => array('pdf'),
        //exe
        '4d5a90' => array('exe'),
    ];

    /**
     * returns array of image file extensions
    */
    public function getImageMimes(): array
    {
        return array('gif', 'jpg', 'png');
    }

    /**
     * returns array of audio file extensions
    */
    public function getAudioMimes(): array
    {
        return array('mp3');
    }

    /**
     * returns array of video file extensions
    */
    public function getVideoMimes(): array
    {
        return array('movi', 'mp4', 'ogg', 'webm');
    }

    /**
     * returns array of media file extensions
    */
    public function getMediaMimes(): array
    {
        return array_merge(
            $this->getImageMimes(),
            $this->getAudioMimes(),
            $this->getVideoMimes()
        );
    }

    /**
     * returns array of document file extensions
    */
    public function getDocumentMimes(): array
    {
        return array('pdf', 'doc', 'docx');
    }

    /**
     * resolves a given extension to something compatible with its internal extension
     *
     *@param string $ext - the extension
     *@return string
    */
    public function resolveExtension(string $ext): string
    {
        $ext = strtolower($ext);
        switch($ext)
        {
            case 'jpeg':
                return 'jpg';
        }
        return $ext;
    }

    /**
     * resolves array of given extensions to what is compatible with its internal extensions
     *
     *@param array $exts - array of extensions
     *@return array
    */
    public function resolveExtensions(array $exts): array
    {
        return array_map([$this, 'resolveExtension'], $exts);
    }

    /**
     * detects and returns array of possible file extensions
     *
     *@param string $filename - the file absolute path
     *@param string [$magic_byte] - stores the magic byte in the variable
     *@throws FileException throws FileException if error occurs while open file for reading
     *@return array
    */
    public function detect(string $filename, string &$magic_byte = null): array
    {
        if(!file_exists($filename))
            throw new FileNotFoundException($filename . ' does not exist');

        $pointer = null;
        try
        {
            $pointer = fopen($filename, 'rb');
            if(!$pointer)
                throw new Exception('error occured while opening file');
        }
        catch(Exception $ex)
        {
            throw new FileReadException($ex->getMessage());
        }

        $byte = fread($pointer, 4);
        $magic_byte = substr(bin2hex($byte), 0, 6); //read the first 4 byte
        return Util::arrayValue($magic_byte, $this->_extensions, array('txt'));
    }
}