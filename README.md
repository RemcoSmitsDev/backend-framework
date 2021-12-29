# PHP Backend Framework
Lightweight PHP Backend Framework. Includes fast and secure Database QueryBuilder,
Advanced Routing with dynamic routes(middlewares,grouping,prefix)

To setup this PHP Backend Framework you need to install this package with composer:
#### Install
```terminal
composer require remcosmits/backend-framework
```

#### Setup
```php
// namespace for App/Route class
use Framework\App;
use Framework\Http\Route\Route;

// include autoloader(composer)
require_once(__DIR__.'/../vendor/autoload.php');

// start app load all default(security) settings
App::start();

// make instance of Route class
$route = new Route();

<!-- all routes -->

// init all routes and check witch route is equals to the current uri
$route->init();
```

#### Basic routing
Supported request methods: `GET`, `POST`, `PUT`, `DELETE`
```php
// route using callback function
$route->get('/account', function () {
    echo "Account page";
});
// route using class methods
$route->get('/account', [AccountController::class,'index']);

// supported request types/methods:
$route->get();
$route->post();
$route->put();
$route->delete();
```

#### Multi request method routing
You can give these routes an name but you can't give them separate names.
```php
// route using multi request types
$route->match('GET|POST','/user/{userID}', function ($userID) {
    echo "user information";
});
```

#### Dynamic routing
```php
// route using dynamic routing(params)
// all params can be accessed with the given name
$route->get('/account/{accountID}', function ($accountID) {
    echo "AccountID: {$accountID}";
});

// You can also change the regex pattern of the dynamic params
// Now accountID can be only an number.
$route->get('/account/{accountID}', function ($accountID) {
    echo "AccountID: {$accountID}";
})->pattern(['accountID' => '[0-9]+']);

// dynamic route prefix
$route->prefix('account')->group(function (Route $route) {
    // group by dynamic prefix param
    $route->prefix('/{accountID}')->group(function (Route $route) {
        // Route will be: /account/{accountID}/profile
        $route->get('/profile', function($accountID){
            echo "Account profile page {$accountID}";
        })->pattern(['accountID' => '[0-9]+']);

        // you can set an other pattern for `{accountID}`
        // route will be: /account/{accountID}/profile
        $route->get('/profile', function($accountID){
            echo "Account profile page {$accountID}";
        })->pattern(['accountID' => '[0-9]+\-[a-z]+']);
    });

    // Route will be: /account
    $route->get('/', function(){
        echo "Account page";
    });
});
```

#### Prefix
```php
// Route will end up like: /account/profile
$route->prefix('account')->get('/profile', function () {
    echo "Account profile page";
});

// Grouped routes with prefix
$route->prefix('account')->group(function (Route $route) {
    // when pattern was not correct
    // Route will end up like: /account/{accountID}
    $route->get('/{accountID}', function ($accountID) {
        // Get accountID from URL
        echo "AccountID: {$accountID}";
    });
});

// Grouped routes with dynamic prefix
$route->prefix('account')->group(function (Route $route) {
    // dynamic route prefix
    $route->prefix('{accountID}')->group(function(Route $route){
        // Route will end up like: /account/{accountID}/profile
        $route->get('/profile', function ($accountID) {
            // Get accountID from URL
            echo "AccountID: {$accountID}";
        })->pattern(['accountID' => '[0-9]+']);
        // you can change the dynamic route prefix pattern after all (get, post, put, delete, match) methods
    });
});
```

#### Middlewares
```php
// Route with single middleware check
$route->middleware(false)->get('/profile', function () {
    echo "Account profile page";
});

// Route with array of middleware checks
$route->middleware([true, false])->get('/profile', function () {
    echo "Account profile page";
});
```

#### Group routing
```php
// Grouped routes with prefix
$route->prefix('account')->group(function (Route $route) {
    // when pattern was not correct
    // Route will end up like: /account/{accountID}
    $route->get('/{accountID}', function ($accountID) {
        // Get accountID from URL
        echo "AccountID: {$accountID}";
    });
});

// Grouped routes with middleware check
$route->middleware(true)->group(function (Route $route) {
    // when pattern was not correct
    // Route will end up like: /account/{accountID}
    $route->get('/{accountID}', function ($accountID) {
        // Get accountID from URL
        echo "AccountID: {$accountID}";
    });
});
```
