<?php

namespace App\Service;


use PDO;
use PDOException;

readonly class ParseDataService
{
    public function __construct(
        private string $filesFolder,
    ) {
    }


    function writeDataToFile($data, $format, $filename) {
        switch ($format) {
            case 'csv':
                $file = fopen($filename, 'w');
                foreach ($data as $row) {
                    fputcsv($file, $row);
                }
                fclose($file);
                break;
            case 'xml':
                $xml = new SimpleXMLElement('<data></data>');
                foreach ($data as $row) {
                    $item = $xml->addChild('item');
                    foreach ($row as $key => $value) {
                        $item->addChild($key, $value);
                    }
                }
                file_put_contents($filename, $xml->asXML());
                break;
            case 'txt':
                $file = fopen($filename, 'w');
                foreach ($data as $row) {
                    fwrite($file, implode("\t", $row) . "\n");
                }
                fclose($file);
                break;
            default:
                echo "Unsupported format";
                break;
        }
    }

    public function getDownloadLink()
    {
    }

    public function convert(array $data): array
    {
        $handledData = [];
        foreach ($data as $item) {
            foreach ($item as $row) {

                $title = trim($row['post_title'], "'\"");
                $content = trim($row['post_content'], "'\"");

                // Now you can use $title and $content for further processing
                // For example, for CSV format
                $handledData[] = [$title, $content];


            }
        }

        return $handledData;
    }


    // Function to get column indices by name
    function getColumnIndices($headerRow) {
        $indices = [];
        foreach ($headerRow as $index => $columnName) {
            $indices[$columnName] = $index;
        }
        return $indices;
    }
}