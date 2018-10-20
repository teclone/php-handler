<?php
declare(strict_types = 1);

namespace Forensic\Handler\Interfaces;

interface FileExtensionDetectorInterface
{
    /** returns array of image file mimes */
    public function getImageMimes(): array;

    /** returns array of audio file mimes */
    public function getAudioMimes(): array;

    /** returns array of video file mimes */
    public function getVideoMimes(): array;

    /** returns array of media file mimes */
    public function getMediaMimes(): array;

    /** returns array of document file mimes */
    public function getDocumentMimes(): array;

    /** returns array of document file mimes */
    public function getArchiveMimes(): array;

    /** resolves a given extension to something compatible with its internal extension */
    public function resolveExtension(string $ext): string;

    /** resolves array of extensions to something compatible with its internal extensions */
    public function resolveExtensions(array $exts): array;

    /** detect file extension */
    public function detect(string $filename): array;
}