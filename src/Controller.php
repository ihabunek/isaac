<?php

namespace Bezdomni\IsaacRebirth;

use Silex\Application;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;


/**
 * Processes the barcode request and forms a response.
 */
class Controller
{
    public function indexAction(Application $app)
    {
        return $app['twig']->render("index.twig");
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
        $data = file_get_contents(__DIR__ . '/../var/saves/' . $id);
        if ($data === false) {
            throw new \Exception("Unable to load savegame data.");
        }

        $save = new SaveGame($data);

        $data = [
            "id" => $id,
            "save" => $save,
            "catalogue" => $save->catalogue()
        ];

        $content = $app['twig']->render("show.twig", $data);

        return new Response($content, 200, [
            'Cache-Control' => 's-maxage=86400'
        ]);
    }
}
