<?php

use BigFish\Hub3\Api\Controller;
use BigFish\Hub3\Api\Validator;
use BigFish\Hub3\Api\Worker;
use BigFish\PDF417\PDF417;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

require __DIR__ . '/../vendor/autoload.php';

$app = new Application();
$app['debug'] = true;

// -- Providers ----------------------------------------------------------------

$app->register(new Silex\Provider\UrlGeneratorServiceProvider());
$app->register(new Silex\Provider\ServiceControllerServiceProvider());

// -- Templating ---------------------------------------------------------------

$app->register(new Silex\Provider\TwigServiceProvider(), [
    'twig.path' => __DIR__ . '/../templates'
]);

$app['twig'] = $app->share($app->extend('twig', function($twig, $app) {
    $twig->addFilter(new Twig_SimpleFilter('markdown', function ($text) {
        $parsedown = new Parsedown();
        return $parsedown->text($text);
    }, ['is_safe' => ['html']]));
    return $twig;
}));

$app->before(function (Request $request) use ($app) {
    $app['twig']->addGlobal('current_path', $request->getPathInfo());
});

// -- Components ---------------------------------------------------------------

$app['controller'] = $app->share(function() use ($app) {
    return new Bezdomni\IsaacRebirth\Controller();
});

// -- Routing ------------------------------------------------------------------

$app->get('/', 'controller:indexAction')
    ->bind("index");

$app->post('/upload', 'controller:uploadAction')
    ->bind("upload");

$app->get('/show/{id}', 'controller:showAction')
    ->assert('id', '[0-9a-f]{32}')
    ->bind("show");


// -- New Relic ----------------------------------------------------------------

if (extension_loaded('newrelic')) {
    newrelic_set_appname("Isaac");

    $app->before(function (Request $request) use ($app) {
        newrelic_name_transaction($request->getPathInfo());
    });
}

// -- Go! ----------------------------------------------------------------------

$app->run();
