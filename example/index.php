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
    $route->middleware(true)->prefix('{accountID}')->group(function (Route $route) {
        // sup group based on accountID
        $route->middleware(true)->get('/', function ($accountID) {
            echo "AccountID: ".$accountID;
        })->pattern(['accountID' => '[0-9]+']);

        // you can change the accountID pattern
        $route->get('/test1', function ($accountID) {
            echo "Account: ".$accountID;
        })->name('test')->pattern(['accountID' => '[0-9]+\-[A-Za-z]+']);
    });

    // when pattern was not correct
    $route->prefix('{accountID}/test2')->group(function (Route $route) {
        // sup group based on accountID
        $route->get('/', function ($accountID) {
            echo "AccountID: ".$accountID;
        })->pattern(['accountID' => '[0-9]+']);

        // you can change the accountID pattern
        $route->get('/test', function ($accountID) {
            echo "Account: ".$accountID;
        })->pattern(['accountID' => '[0-9]+\-[A-Za-z]+']);
    });
});

// $route->match('POST|GET', '/test/{abb}', function ($abb) {
//     echo "test route{$abb}";
// });


// init all routes(check route against current url by request method)
$route->init();
