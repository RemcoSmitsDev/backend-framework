<?php

use Framework\Http\Route\Route;
use Framework\Http\Response;
use Framework\App;

use Framework\Database\DatabaseV2;

require_once(__DIR__ . '/../vendor/autoload.php');

// start app load all default(security) settings
$app = App::start();

// make instance of route class
$route = new Route();

$DB = new DatabaseV2();


// $result = $DB->table('el_users')->select(['first_name', 'id' => function ($query) {
//     $query->table('el_role', 'count(id)')->where('users_id', 1);
// }])->where(function ($query) {
//     $query->where('id', 1)->orWhere('id', 8);
// })->all();

// $result = $DB->table('el_users')->select(['role' => function ($query) {
//     $query->table('el_role', 'count(id)')->where('el_users.id', 1);
// }])->all(['not found']);

// $result = $DB->table('el_users')->whereIn('id', [1, 2, 3])->all(['not found']);

var_dump($DB->table('el_roles')->insert([
    [
        'role_name' => 'adsjkfasld'
    ],
    [
        'role_name' => 'adsjkfasld2'
    ]
]));

// $result = $DB->table('el_users', ['*'])->whereIn('id', [1,2,3])->all(['asd']);


// dd($result);










exit();

// routing example
$route->prefix('account')->group(function (Route $route) {
    // route for accountID
    // with route pattern
    $route->middleware(true)->prefix('{accountID}')->group(function (Route $route) {
        // sup group based on accountID
        $route->middleware(true)->get('/', function ($accountID) {
            echo "AccountID: " . $accountID;
        })->pattern(['accountID' => '[0-9]+']);

        // you can change the accountID pattern
        $route->get('/test1', function ($accountID) {
            echo "Account: " . $accountID;
        })->name('test')->pattern(['accountID' => '[0-9]+\-[A-Za-z]+']);
    });

    // when pattern was not correct
    $route->prefix('{accountID}/test2')->group(function (Route $route) {
        // sup group based on accountID
        $route->get('/', function ($accountID) {
            echo "AccountID: " . $accountID;
        })->pattern(['accountID' => '[0-9]+']);

        // you can change the accountID pattern
        $route->get('/test', function ($accountID) {
            echo "Account: " . $accountID;
        })->pattern(['accountID' => '[0-9]+\-[A-Za-z]+']);
    });
});

// $route->match('POST|GET', '/test/{abb}', function ($abb) {
//     echo "test route{$abb}";
// });


// init all routes(check route against current url by request method)
$route->init();
