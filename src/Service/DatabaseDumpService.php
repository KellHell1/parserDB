<?php

namespace App\Service;


use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\File;

readonly class DatabaseDumpService
{
    public function __construct(
        private string $dumpsFolder,
    ) {
    }

    public function getAllFilesInDirectory(): array
    {
        $finder = new Finder();

        $directory = $this->dumpsFolder;

        $finder->files()->in($directory);

        $fileNames = [];

        foreach ($finder as $file) {
            $fileNames[] = $file->getFilename();
        }

        return $fileNames;
    }

    public function getOneFileInDirectory(string $fileName): ?string
    {
        $filePath = $this->dumpsFolder . '/' . $fileName;

        if (is_file($filePath)) {
            return file_get_contents($filePath);
        }

        // File not found
        return null;
    }

    public function addFileToDirectory(string $directory, string $fileName, $fileContent): bool
    {
        $filesystem = new Filesystem();
        $filePath = $directory . '/' . $fileName;

        // Check if the file already exists
        if ($filesystem->exists($filePath)) {
            return false; // File already exists, return false
        }

        // Write the file content to the specified directory
        try {
            $filesystem->dumpFile($filePath, $fileContent);
            return true; // File added successfully
        } catch (\Exception $e) {
            // Handle the exception if any
            return false; // Failed to add the file
        }
    }
}