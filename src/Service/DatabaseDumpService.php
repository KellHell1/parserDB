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

    public function getPostsDataFromFileInDirectory(string $fileName): array
    {
        $sqlDump = file_get_contents("$this->dumpsFolder/$fileName");

        // Split SQL commands
        $commands = explode(';', $sqlDump);

        // Iterate through each command
        foreach ($commands as $command) {
            // Look for INSERT statements
            if (stripos($command, 'INSERT INTO') !== false) {
                // Extract table name from INSERT statement
                preg_match('/INSERT INTO `([^`]*)`/', $command, $matches);
                $tableNameInCommand = $matches[1] ?? '';

                // Check if the table name ends with 'posts'
                if (substr_compare($tableNameInCommand, 'posts', -strlen('posts')) === 0) {
                    $insertData = $this->extractDataFromInsert($command);

                }
            }
        }

        return $insertData ?? [];
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

    // Function to extract data from INSERT statements
    function extractDataFromInsert($insertStatement): array
    {
        // Extract the values part of the INSERT statement
        preg_match('/\((.*?)\)/', $insertStatement, $matches);
        $values = $matches[1];

        // Split the values into an array
        $valuesArray = explode('),(', $values);

        // Iterate through each value set and extract data
        $data = [];
        foreach ($valuesArray as $valueSet) {
            // Remove parentheses and split values by comma
            $values = explode(',', $valueSet);

            // Clean each value (remove quotes and trim)
            $cleanedValues = [];
            foreach ($values as $value) {
                $cleanedValues[] = trim(trim($value, "'"), '"');
            }

            $data[] = $cleanedValues;
        }

        return $data;
    }
}