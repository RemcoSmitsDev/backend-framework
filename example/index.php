<?php

use Framework\Http\Route\Route;
use Framework\Http\Response;
use Framework\App;

require_once(__DIR__.'/../vendor/autoload.php');

// start app load all default(security) settings
$app = App::start();

// make instance of route class
$route = new Route();

// routing example
$route->prefix('account')->group(function (Route $route) {
    // route for accountID
    // with route pattern
    $route->middleware([true, true == true])->prefix('{accountID}')->group(function (Route $route) {
        // sup group based on accountID
        $route->get('/', function ($accountID) {
            echo "AccountID: ".$accountID;
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

// init all routes(check route against current url by request method)
$route->init();
