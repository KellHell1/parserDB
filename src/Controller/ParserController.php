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

    #[Route('/')]
    public function main(DatabaseDumpService $databaseDumpService): Response
    {
        $allDumps = $databaseDumpService->getAllFilesInDirectory();

        return $this->render('parser/main.html.twig', ['allDumps' => $allDumps]);
    }


    #[Route('/parse/dump', name: 'parse_dump', methods: ['POST'])]
    public function parseDumpData(Request $request, DatabaseDumpService $databaseDumpService): Response
    {
        $dumpFiles = $request->get('databases');

        // Підключаємося до бази даних
        $dsn = 'mysql:host=database:3306;dbname=test';
        $username = 'symfony';
        $password = 'symfony';

        $pdo = new PDO($dsn, $username, $password);

        foreach ($dumpFiles as $file) {
            try {
                $pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
                $query = file_get_contents("uploads/dumps/$file");
                $pdo->exec($query);

            } catch (PDOException $e) {
                echo 'Помилка підключення: ' . $e->getMessage();
                die();
            }
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