<?php
declare(strict_types = 1);

namespace Forensic\Handler\Interfaces;

interface FileExtensionDetectorInterface
{
    /** resolves a given extension to something compatible with its internal extension */
    public function resolveExtension(string $ext): string;

    /** resolves array of extensions to something compatible with its internal extensions */
    public function resolveExtensions(array $exts): array;

    /** detect file extension */
    public function detect(string $filename): array;
}