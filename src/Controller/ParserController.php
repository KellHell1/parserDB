<?php

namespace App\Controller;


use App\Service\DatabaseDumpService;
use App\Service\ParseDataService;
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
    public function parseDumpData(Request $request, DatabaseDumpService $databaseDumpService, ParseDataService $parseDataService): Response
    {
        $dumpFiles = $request->get('databases');
        $formats = $request->get('formats');

        $data = [];
        foreach ($dumpFiles as $file) {
            $data[] = $databaseDumpService->getPostsDataFromFileInDirectory($file);
        }

        // Process extracted data (e.g., save to database, manipulate, etc.)
        $parsedData = $parseDataService->convert($data);

        foreach ($formats as $format) {
            $parseDataService->writeDataToFile($parsedData, $format, "uploads/files/test.$format");
        }

        return new Response('hi');
    }

    #[Route('/add/dump')]
    public function uploadDump(): Response
    {
        return new Response('hi');
    }
}