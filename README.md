# PHP Backend Framework

[![PHP tests](https://github.com/RemcoSmitsDev/backend-framework/actions/workflows/php.yml/badge.svg?branch=master)](https://github.com/RemcoSmitsDev/backend-framework/actions/workflows/php.yml)

Lightweight PHP Backend Framework. Includes fast and secure Database QueryBuilder,
Advanced Routing with dynamic routes([middlewares](#route-middlewares), [groups](#route-group), [prefixes](#route-prefix), [dynamic routing](#dynamic-routing), [named routes](#named-routes))

To setup this PHP Backend Framework you need to install this package with composer:

#### Install

```bash
composer require remcosmits/backend-framework
```

- [Setup](#setup)
 - [Routing](#routing) 
- [Response](#response)
- [Database](#querybuilder)

#### Setup

```php
// namespace for App/Route class
use Framework\Http\Route\Route;
use Framework\App;

// include autoloader(composer)
require_once(__DIR__.'/../vendor/autoload.php');

// make instance of App
$app = new App();

// start app load all default(security) settings
$app->start();

// when you want to make a singleton you can do that like:
$app->setInstance(
    new Route(),
    new ClassThatYouWant()
);

// When you want to use the instance you can do that like this:
/** @var Route */
1. $app->getInstance('route');
2. app()->getInstance('route');
3. app('route');

// make instance of Route class
$route = new Route();

<!-- all routes -->

// init all routes and check witch route is equals to the current uri
$route->init();
```

## Routing
#### Basic routing
Supported request methods: `GET`, `POST`, `PUT`, `DELETE`, `PATCH`
```php
// supported request types/methods:
$route->get();
$route->post();
$route->put();
$route->patch();
$route->delete();

// route using callback function
$route->get('/account', function () {
    echo "Account page";
});

// route using class methods
$route->get('/account', [AccountController::class,'index']);

// route using multi request methods(suports all requests methods)
$route->match('GET|POST','/user/{userID}', function ($userID) {
    echo "user information";
});
```

#### Dynamic routing
For dynamic routing you can use the `pattern` method to allow only specific values for a dynamic parameter. You can do this on each route method action `GET`, `POST`, `PUT`, `DELETE`, `PATCH` or `match` method(that allows multiple request methods). You can event overwrite them when you are in a nested route.
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

### Named routes
When you want to get a route by his name you can use the `getRouteByName` method, or use `redirect()->route()`. When you want to access a dynamic named route you need to pass in the given values by his key.
```php
// show all accounts
route()->getRouteByName('account.index');

// Thhis will get a single route
route()->getRouteByName('account.show', ['accountID' => 1]);

// This will redirect you to the route
redirect()->route('account.index');

// This will redirect you to the route with dynamic param
redirect()->route('account.show', ['accountID' => 1]);
```

#### Route prefix
A prefix can be used to prevent writing long rout uri's and group routes that all have a part inside the uri that is the same. You can use this method before a `single route` or a `group`. A prefix can also contain a dynamic route. You can then specify the pattern on each request method `GET`, `POST`, `PUT`, `DELETE`, `PATCH` or `match` method(that allows mulitple request methods).
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
        // you can change the dynamic route prefix pattern after all (get, post, put, delete, patch) methods
    });
});
```

#### Route middlewares
Middlewares can be used to prevent access to request methods `GET`, `POST`, `PUT`, `DELETE`, `PATCH` or `match` method(that allows mulitple request methods)
```php
// Route with single middleware check
$route->middleware(false)->get('/profile', function () {
    echo "Account profile page";
});

// Route with array of middleware checks
$route->middleware([true, false])->get('/profile', function () {
    echo "Account profile page";
});
// OR
$route->middleware(true, false)->get('/profile', function () {
    echo "Account profile page";
});

// Route with custom validate class
$route->middleware(true, CustomMiddlewareClass::class)->get('/profile', function () {
    echo "Account profile page";
});
// The class shout like this:
class CustomMiddlewareClass
{
    public function handle(array $route, Closure $next){
        // return false when middleware need to fail
        if(true !== false){
            return false;
        }
        
        // when middleware is successful
        return $next();
    }
}
```

#### Route group
Grouping routes can be very nice when you have middlewares/a prefix that need to apply to a number of routes.
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

## Request
### Request methods
`request()->all()` Will get all request information from `GET`, `POST`, `FILES`
```php
request()->all();
```

`request()->get()` Will get all request information from `GET`
```php
$_GET['name'] = 'test';
request()->get('name'); // test
```

`request()->post()` Will get all request information from `POST`
```php
$_POST['name'] = 'test';
request()->post('name'); // test
```

`request()->file()` Will get all request information from `FILES`
```php
$_FILES['name'] = []; // showing purpose(invalid file array)
request()->file('name'); // will get file array
```

`request()->cookies()` Will get all cookies from `request`|`server`
```php
$_SERVER['Cookie'] = 'PHPSESSID=u30vn0lgpmf6010ro4ol9snle1; name=test';
request()->cookies('name'); // test
```

`request()->server()` Will get all server headers from `SERVER`
```php
$_SERVER['HTTP_METHOD'] = 'GET';
request()->server('HTTP_METHOD'); // GET
```

`request()->headers()` Will get all request headers from `getallheaders`|`SERVER`
```php
header('Content-Type: application/json;');
request()->headers('Content-Type'); // application/json
```

### Request validate methods
```php
request()->validate();
request()->csrf();
request()->validateCsrf();
```

## Response
The response class/helper will help you with sending a response back with the correct headers and information. It will automaticly pick the right `Content-Type` when you send a response back. The default `responsecode` is `200` with the message `OK`. Every response code contains its own message that will be automaticly included.
You can chain all methods and the response will be returned on the last method chain.
### Methods
`response()->json()` Will transform all information into json.
```php
response()->json(['this is a array to json']);
// headers
Content-Type: application/json; charset=UTF-8;
HTTP/1.1 200 OK
```

`response()->text()` allows text/html
```php
response()->text('this is a normal string|html');
// headers
Content-Type: text/html; charset=UTF-8;
HTTP/1.1 200 OK
```

`response()->code()` Will set the responsecode this uses `http_response_code` under the hood
```php
response()->code(404);
// headers
Content-Type: text/html; charset=UTF-8;
HTTP/1.1 404 Not Found
```

`response()->headers()` Will append headers with response
```php
response()->headers(['Test' => 'test']);
// headers
Test: test
Content-Type: text/html; charset=UTF-8;
HTTP/1.1 200 OK
```

`response()->exit()` Will use the `exit` function from php when response was send
```php
response()->json(['message' => 'Something went wrong'])->exit();
```

`response()->view()` Will append view(content file) to response
```php
response()->view('index',['userIds' => [1,2,3,4]]);
// headers
Content-Type: text/html; charset=UTF-8;
HTTP/1.1 200 OK
```
