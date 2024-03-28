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


    function extractDataFromInsert($insertQuery) {
        // Извлекаем названия полей
        preg_match('/\((.*?)\)/', $insertQuery, $matches);
        $fields = explode(',', $matches[1]);

        // Удаляем обратные кавычки из названий полей
        $fields = array_map('trim', $fields);
        $fields = array_map(function($field) {
            return trim($field, '`');
        }, $fields);

        // Извлекаем значения в скобках после VALUES
        $start = strpos($insertQuery, "VALUES") + strlen("VALUES");
        $end = strrpos($insertQuery, ")");
        $valuesSubstring = substr($insertQuery, $start, $end - $start);

        // Разбиваем строку по значениям в скобках
        $valuesArray = explode("),", $valuesSubstring);

        // Удаляем лишние символы и разделяем значения
        foreach ($valuesArray as &$value) {
            $value = str_replace(array('(', ')', "\t"), '', $value);
            $value = explode(',', $value);
        }

        // Создаем ассоциативные массивы, используя названия полей в качестве ключей
        $result = array();
        foreach ($valuesArray as $value) {
            $row = array();
            foreach ($fields as $index => $field) {
                $row[$field] = trim($value[$index]);
            }
            $result[] = $row;
        }

        return $result;
    }
}