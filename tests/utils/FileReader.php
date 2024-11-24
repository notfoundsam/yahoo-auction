<?php

namespace Tests\Utils;

use InvalidArgumentException;

class FileReader
{
    private $directory;

    public function __construct(string $directory)
    {
        $this->directory = $directory;
    }

    public function readFile(string $filename): string
    {
        $filepath = $this->directory . '/' . $filename;

        if (!file_exists($filepath)) {
            throw new InvalidArgumentException("File not found: {$filepath}");
        }

        return file_get_contents($filepath);
    }
}
