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

        // Парсинг значений с учётом тегов HTML
        $valuesArray = [];
        $inQuotes = false;
        $currentValue = '';
        $depth = 0;
        for ($i = 0; $i < strlen($valuesSubstring); $i++) {
            $char = $valuesSubstring[$i];
            if ($char == "'" && ($i == 0 || $valuesSubstring[$i - 1] != "\\")) {
                $inQuotes = !$inQuotes;
            }
            if ($char == '(' && !$inQuotes) {
                $depth++;
            }
            if ($char == ')' && !$inQuotes) {
                $depth--;
            }
            if ($char == ',' && !$inQuotes && $depth == 0) {
                $valuesArray[] = $currentValue;
                $currentValue = '';
            } else {
                $currentValue .= $char;
            }
        }
        $valuesArray[] = $currentValue;

        // Удаляем лишние символы и разделяем значения
        foreach ($valuesArray as &$value) {
            $value = trim($value, ", \t\n\r\0\x0B");
            $value = preg_replace('/\s+/', ' ', $value); // Заменяем последовательности пробелов на одиночные
            $value = preg_replace('/(?<!\\\\)\\\\(?![\'"])|(?<=\\\\\\\\)\\\\(?![\'"])/', '', $value); // Удаляем экранирование, кроме экранирования кавычек
            $value = preg_split('/,(?=(?:[^\'"]|\'[^\']*\'|"[^"]*")*$)/', $value); // Разбиваем строку по запятым, не находящимся внутри кавычек
            $value = array_map(function($item) {
                return trim($item, ", \t\n\r\0\x0B'\"");
            }, $value);
        }

        // Создаем ассоциативные массивы, используя названия полей в качестве ключей
        $result = array();
        foreach ($valuesArray as $value) {
            $row = array();
            foreach ($fields as $index => $field) {
                $row[$field] = isset($value[$index]) ? $value[$index] : null;
            }
            $result[] = $row;
        }

        return $result;
    }
}