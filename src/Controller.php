<?php

namespace Bezdomni\IsaacRebirth;

use SplFileInfo;

use Silex\Application;

use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Processes the barcode request and forms a response.
 */
class Controller
{
    public function indexAction(Application $app)
    {
        $dirPath = __DIR__ . '/../var/saves';

        $sort = function (SplFileInfo $a, SplFileInfo $b) {
            return strcmp($b->getCTime(), $a->getCTime());
        };

        // Find recent uploads
        $finder = new Finder();
        $finder->files()
            ->in($dirPath)
            ->date('since yesterday')
            ->sort($sort);

        $recents = [];
        foreach ($finder as $file) {
            $recents[] = [
                "time" => $file->getCTime(),
                "hash" => $file->getFilename(),
            ];
        }

        $content = $app['twig']->render("index.twig", [
            "recents" => $recents
        ]);

        return new Response($content, 200, [
            'Cache-Control' => 's-maxage=300'
        ]);
    }

    public function uploadAction(Application $app, Request $request)
    {
        // Read file from request
        $file = $request->files->get('savegame');
        if ($file === null) {
            throw new BadRequestHttpException("Savegame data not found in request.");
        }

        // Check size
        $size = $file->getSize();
        if ($size !== 4096) {
            throw new BadRequestHttpException("Unexpected savegame file size: $size B. Expected 4096 B.");
        }

        // Read the file into memory
        $fp = $file->openFile();
        $contents = $fp->fread(4096);

        // Check header
        $header = substr($contents, 0, 14);
        if ($header !== 'ISAACNGSAVE06R') {
            throw new BadRequestHttpException("Invalid file header: \"$header\". Expected \"ISAACNGSAVE06R\". Probably wrong save version.");
        }

        // Save the file
        $md5 = md5($contents);
        $filePath = __DIR__ . '/../var/saves/' . $md5;
        if (false === file_put_contents($filePath, $contents)) {
            throw new \Exception("Failed saving savegame data to $filePath");
        }

        // Redirect to show
        return $app->redirect('/show/' . $md5);
    }

    public function showAction(Application $app, Request $request, $id)
    {
        $dirPath = __DIR__ . '/../var/saves';
        $filePath = "$dirPath/$id";

        $fileInfo = new SplFileInfo($filePath);
        if (!$fileInfo->isFile()) {
            throw new \Exception("Unable to load savegame data.");
        }

        $ctime = $fileInfo->getCTime();

        $file = $fileInfo->openFile();
        $size = $file->getSize();
        $contents = $file->fread($size);

        $save = new SaveGame($contents);

        $data = [
            "id" => $id,
            "save" => $save,
            "ctime" => $ctime,
            "catalogue" => $save->catalogue()
        ];

        $content = $app['twig']->render("show.twig", $data);

        return new Response($content, 200, [
            'Cache-Control' => 's-maxage=86400'
        ]);
    }
}
