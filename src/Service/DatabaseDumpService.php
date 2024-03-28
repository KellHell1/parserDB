<?php

namespace App\Service;


use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;

class DatabaseDumpService
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
                if (str_contains($tableNameInCommand, 'posts')) {
                    $insertData = $this->extractDataFromInsert($command);
                }
            }
        }

        return $insertData ?? [];
    }

    public function addFileToDirectory($file): bool
    {
        $fileName = $file->getClientOriginalName();

        // Move the file to the desired directory
        try {
            $file->move(
                $this->dumpsFolder, // Specify the directory where you want to save the uploaded files (defined in services.yaml or parameters.yml)
                $fileName
            );

            // File uploaded successfully
            return ('File uploaded successfully!');
        } catch (FileException $e) {
            // Handle file upload error
        }

        return false;
    }


    function extractDataFromInsert($insertQuery): array
    {
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
        unset($value);
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