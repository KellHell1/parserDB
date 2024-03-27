<?php

namespace App\Controller;


use App\Service\DatabaseDumpService;
use App\Service\ParseDataService;
use PDO;
use PDOException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ParserController extends AbstractController
{
    public function __construct(
        DatabaseDumpService $databaseDumpService,
        ParseDataService    $parseDataService,
    ) {
    }

    #[Route('/test')]
    public function main(DatabaseDumpService $databaseDumpService): Response
    {
        $allDumps = $databaseDumpService->getAllFilesInDirectory();

        return $this->render('parser/main.html.twig', ['allDumps' => $allDumps]);
    }


    #[Route('/parse/dump', name: 'parse_dump', methods: ['POST'])]
    public function parseDumpData(Request $request, DatabaseDumpService $databaseDumpService): Response
    {
        // databases
        $dumpFiles = $request->get('databases');
        dd($dumpFiles);

        // Читаємо вміст SQL-дампу з файлу
        $sqlDumpContent = file_get_contents('uploads/dumps/db1.sql');

        // Розбиваємо на окремі SQL-запити
        $sqlQueries = explode(';', $sqlDumpContent);

        // Підключаємося до бази даних
        $dsn = 'mysql:host=database:3306;dbname=test';
        $username = 'symfony';
        $password = 'symfony';

        try {
            $pdo = new PDO($dsn, $username, $password);
        } catch (PDOException $e) {
            echo 'Помилка підключення: ' . $e->getMessage();
            die();
        }

        // MySQL dump command
        $command = "mysqldump --host={database:3306} --user={$username} --password={$password} {test} > uploads/dumps/db1.sql";

        // Execute the command
        $output = shell_exec($command);

        if ($output === null) {
            echo "Database dump successful.";
        } else {
            echo "Error creating database dump: $output";
        }

        // Закриваємо з'єднання з базою даних
        $pdo = null;

        return new Response('hi');
    }

    #[Route('/add/dump')]
    public function addDump(): Response
    {
        return new Response('hi');
    }
}