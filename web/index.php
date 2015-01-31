<?php

use BigFish\Hub3\Api\Controller;
use BigFish\Hub3\Api\Validator;
use BigFish\Hub3\Api\Worker;
use BigFish\PDF417\PDF417;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

require __DIR__ . '/../vendor/autoload.php';

$app = new Application();

// -- Settings -----------------------------------------------------------------

// Default settings
$settings = [
    'debug' => false,
    'cache' => false,
    'ga_code' => null,
    'maintenance' => false,
    'database' => [
        'dsn' => 'pgsql:host=localhost;dbname=isaac',
        'user' => 'root',
        'pass' => null
    ]
];

$path = __DIR__ . '/../etc/settings.php';
if (file_exists($path)) {
    include $path;
}

$app['settings'] = $settings;

if ($settings['debug']) {
    $app['debug'] = true;
}

// -- Providers ----------------------------------------------------------------

$app->register(new Silex\Provider\UrlGeneratorServiceProvider());
$app->register(new Silex\Provider\ServiceControllerServiceProvider());
$app->register(new Silex\Provider\HttpCacheServiceProvider(), [
    'http_cache.cache_dir' => __DIR__ . '/../cache/',
]);

// -- Templating ---------------------------------------------------------------

$app->register(new Silex\Provider\TwigServiceProvider(), [
    'twig.path' => __DIR__ . '/../templates'
]);

$app->before(function (Request $request) use ($app) {
    $app['twig']->addGlobal('app', $app);
    $app['twig']->addGlobal('current_path', $request->getPathInfo());
});

// -- Components ---------------------------------------------------------------

$app['pdo'] = $app->share(function() use ($app) {
    $dsn = $app['settings']['database']['dsn'];
    $user = $app['settings']['database']['user'];
    $pass = $app['settings']['database']['pass'];

    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

    return $pdo;
});

$app['archiver'] = $app->share(function() use ($app) {
    return new Bezdomni\IsaacRebirth\Archiver(
        $app['pdo']
    );
});

$app['controller'] = $app->share(function() use ($app) {
    return new Bezdomni\IsaacRebirth\Controller(
        $app['archiver']
    );
});

// -- Routing ------------------------------------------------------------------

$app->get('/', 'controller:indexAction')
    ->bind("index");

$app->post('/upload', 'controller:uploadAction')
    ->bind("upload");

$app->get('/show/{hash}', 'controller:showAction')
    ->assert('hash', '[0-9a-f]{32}')
    ->bind("show");

$app->get('/download/{hash}', 'controller:downloadAction')
    ->assert('hash', '[0-9a-f]{32}')
    ->bind("download");

// -- New Relic ----------------------------------------------------------------

if (extension_loaded('newrelic')) {
    newrelic_set_appname("Isaac");

    $app->before(function (Request $request) use ($app) {
        newrelic_name_transaction($request->get("_route"));
    });
}

// -- Maintenance mode ---------------------------------------------------------

$app->before(function (Request $request) use ($app) {
    if ($app['settings']['maintenance']) {
        $msg = '<h1>Binding of Isaac: Rebirth - Savegame parser</h1>';
        $msg .= '<p>Site is down for maintenance. Check back soon.</p>';
        return new Response($msg);
    }
});

// -- Go! ----------------------------------------------------------------------

if ($app['settings']['cache']) {
    Request::setTrustedProxies(['127.0.0.1']);
    $app['http_cache']->run();
} else {
    $app->run();
}
