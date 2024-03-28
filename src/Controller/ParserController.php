<?php

namespace App\Controller;


use App\Service\DatabaseDumpService;
use App\Service\ParseDataService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
    public function main(
        DatabaseDumpService $databaseDumpService,
        ParseDataService $parseDataService
    ): Response
    {
        $allDumps = $databaseDumpService->getAllFilesInDirectory();
        $files = $parseDataService->getAllFilesInDirectory();

        return $this->render('parser/main.html.twig', ['allDumps' => $allDumps, 'files' => $files]);
    }


    #[Route('/parse/dump', name: 'parse_dump', methods: ['POST'])]
    public function parseDumpData(
        Request $request,
        DatabaseDumpService $databaseDumpService,
        ParseDataService $parseDataService
    ): Response
    {
        $dumpFiles = $request->get('databases');
        $formats = $request->get('formats');

        $data = [];
        foreach ($dumpFiles as $file) {

            $trimmedString = strstr($file, '.', true); // Получаем все символы до первой точки

            $data[] = $databaseDumpService->getPostsDataFromFileInDirectory($file);

            $parsedData = $parseDataService->convert($data);

            foreach ($formats as $format) {
                $parseDataService->writeDataToFile($parsedData, $format, "uploads/files/" . $trimmedString . '.' . $format);
            }
        }

        return new RedirectResponse('/');
    }

    #[Route('/add/dump')]
    public function uploadDump(
        Request $request,
        DatabaseDumpService $databaseDumpService
    ): Response
    {
        foreach ($request->files->all() as $file) {
            $databaseDumpService->addFileToDirectory($file);
        }

        return new Response('success', 200);
    }
}