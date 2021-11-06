<?php

use Framework\Http\Route\Route;
use Framework\Http\Response;
use Framework\App;

require_once(__DIR__.'/../vendor/autoload.php');
// require helper functions
require_once(__DIR__.'/../src/helperFunctions.php');


$route = new Route();

// $route->get('/test/{id}', function () {
//     // echo "string";
// })->name('test')->pattern(['id' => '[0-9]+']);

$route->prefix('test')->group(function (Route $route) {
    $route->get('{id}', function ($id) {
        echo "string".$id;
    })->pattern(['id' => '[0-9]+']);

    $route->get('{id}/remco', function (Response $response) {
        // echo "string".$id.'<br><br>';
        $response->json(['test' => 'aksdfjsalkdfjlksadf']);
    });
});


$route->init();
