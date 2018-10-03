<?php
declare(strict_types = 1);

namespace Forensic\Handler\Interfaces;

interface FileExtensionDetectorInterface
{
    /** detect file extension */
    public function detect(string $filename): array;
}