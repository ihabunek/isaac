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
    /**
     * Database access object.
     *
     * @var Bezdomni\IsaacRebirth\Archiver
     */
    private $archiver;

    public function __construct(Archiver $archiver)
    {
        $this->archiver = $archiver;
    }

    public function indexAction(Application $app)
    {
        $recents = $app['archiver']->recent();

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
            throw new UserException("Savegame data not found in request. Did you
                chose a savegame before pressing Submit?");
        }

        // Read the file into memory
        $data = $file->openFile()->fread(4096);

        // Check header
        $header = substr($data, 0, 14);
        if ($header !== 'ISAACNGSAVE06R') {
            throw new UserException("Invalid file header: \"$header\". Expected
                \"ISAACNGSAVE06R\". You either uploaded a file wich is not a
                BOIR savegame, or the savegame version is not supported.");
        }

        // Calculate the hash which is used to identify the savegame
        $hash = md5($data);

        // If file already exists, skip the upload
        if ($app['archiver']->exists($hash)) {
            return $app->redirect('/show/' . $hash);
        }

        // Save the file
        $app['archiver']->save($data);

        // Redirect to show
        return $app->redirect('/show/' . $hash);
    }

    public function showAction(Application $app, Request $request, $hash)
    {
        $record = $app['archiver']->load($hash);
        if ($record === null) {
            $app->abort(404, "Savegame not found");
        }

        $data = base64_decode($record->data);
        $save = new SaveGame($data);

        $content = $app['twig']->render("show.twig", [
            "hash" => $record->hash,
            "save" => $save,
            "uploaded" => $record->uploaded,
            "catalogue" => $save->catalogue()
        ]);

        return new Response($content, 200, [
            'Cache-Control' => 's-maxage=86400'
        ]);
    }

    public function downloadAction(Application $app, Request $request, $hash)
    {
        $record = $app['archiver']->load($hash);
        if ($record === null) {
            $app->abort(404, "Savegame not found");
        }

        $data = base64_decode($record->data);

        return new Response($data, 200, [
            'Cache-Control' => 's-maxage=86400',
            'Content-Disposition' => 'attachment; filename=persistentgamedataX.dat'
        ]);
    }
}
