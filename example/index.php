<?php

use Framework\Http\Route\Route;
use Framework\Http\Response;
use Framework\App;

require_once(__DIR__.'/../vendor/autoload.php');

$app = new App();
$app->start();

$route = new Route();

$route->prefix('account')->group(function (Route $route) {
    // route for accountID
    // with route pattern
    $route->middleware([true, true == true])->prefix('{accountID}')->group(function (Route $route) {
        // sup group based on accountID
        $route->middleware(false)->get('/', function ($accountID) {
            echo "Account: ".$accountID;
        })->pattern(['accountID' => '[0-9]+']);

        // you can change the accountID pattern
        $route->get('/test', function ($accountID) {
            echo "Account: ".$accountID;
        })->pattern(['accountID' => '[0-9]+\-[A-Za-z]+']);
    });

    // when pattern was not correct
    $route->get('/{ID}', function (Response $response, $ID) {
        // echo json response
        $response->json(['ID' => $ID]);
    });
});

$route->init();
