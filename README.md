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
- [Content](#content)
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
The request methods closures have a powerfull [`injection container`](https://riptutorial.com/php/example/4682/constructor-injection) support that will auto include the dependencies based on the parameters.
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
For dynamic routing you can use the `pattern` method to allow only specific values for a dynamic parameter. You can do this on each route method action `GET`, `POST`, `PUT`, `DELETE`, `PATCH` or `match` method(that allows multiple request methods). You can even overwrite them when you are in a nested routes.
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

## Redirect
The **redirect** function can be used to redirect to a `page` or `route`. It will automatic make correct url with: **protocol**, **host**, **uri**

## Content
#### Meanings:
`layout`: is a file that contains the html structure like (`head`|`body`|`footer`). That file needs to have the method `content()->renderTemplate()` this will render the template that you set using `content()->template('posts')->title('This page shows all posts')`.
```html
<html>
    <head>
    <title><?= content()->getTitle(); ?></title>
    </head>
    <body>
        <div class="container">
            // this will render the template that you set using `content()->template('posts', ['posts' => []])`
            <php content()->renderTemplate(); ?>
        </div>
    </body>
</html>
```
`template`: is a file that contains the global structure of page for example: `posts` this will have all the posts inside them. Each `post` wil be a view/component. 
```html
<div>
    <?php content()->view('sidebar'); ?>
    <div>
        <?php content()->view('topbar'); ?>
        <main>
            <?php foreach($posts as $post): ?>
                <?php content()->view('post', compact('post')); ?>
            <?php endforeach; ?>
        </main>
    </div>
</div>
```
`view`: is a file that is also called a `component` for example a single `post` inside the posts template. You will get the `$post` variable from the `content()->view('post', compact('post'))` passed true. **A view will always render directly!**
```html
<div>
    <span><?= clearInjections($post->title); ?></span>
    <p><?= nl2br(clearInjections($post->body)); ?></p>
</div>
```

#### Content methods
`content(viewPath: string|null = null, defaultLayout: string|false = false)`
You want to use this inside your **index.php** before you want to use the **Content** methods. At the end of the **index.php** file you want to set `content()->listen()` this will show a **template** inside the right **layout**.
```php
$app->setInstance(
    new Content(
        '/path/to/all/templates' // The default path is: `SERVER_ROOT.'/../templates'`
        'path from templates path + layout name' // defualt value is `false` you need to write the layout without the extention(`.php`)
    )
);
```

`template(template: string, data: array<string,mixed>)`
The `data` parameter will allow you to use information inside all your views as a variable using the key of the array.
```php
content()->template('posts.index', ['posts' => []]); // set the template where all posts will display
```

`view(view: string, data: array<string,mixed>)` 
The `data` parameter will allow you to use information inside all your views as a variable using the key of the array.
```php
content()->view('post', ['post' => ['title' => 'test']]);
```

`content()->title()` This can set the **title** that you can get inside your layout with `content()->getTitle()`. You can only use it before your template is renderd.
```php
content()->template('posts')->title('This is a test title for all posts');
```

`content()->renderTemplate()` This will render the template that you have set inside the **layout**.
```html
<html>
    <head>
    <title><?= content()->getTitle(); ?></title>
    </head>
    <body>
        <div class="container">
            // This will render the template that your set
            <php content()->renderTemplate(); ?>
        </div>
    </body>
</html>
```

`content()->listen()` This will listen for what **layout** to render. This must be at the bottom of your `index.php`.

## Request
#### Request methods
`request()->all()` Will get all request information from `GET`, `POST` (php://input), `FILES`
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
To validate request inputs you want to use `request()->validate()`
```php
// Rules:
// - string
// - int
// - float
// - array
// - min:_NUMBER_
// - max:_NUMBER_
// - regex:_REGEX_ //without / before and after
// - email
// - url
// - ip
// - YourCustomRuleClass::class // that needs to extend `CustomRule` and must have the `validate` method

$_GET['test'] = '';
// this will fail (min:1)
$validated = request()->validate([
    'test' => ['required', 'string', 'min:1', 'max:255']
]);

if($validated->failed()){
    // do action
    $messages = $validated->getErrorMessages(); // get error messages
    $failedRules = $validated->getFailedRules();
}

// get validated data
$validatedData = $validated->getData();
```
#### Custom validate rule
```php
class YourCustomRuleClass extends CustomRule {
    public function validate(mixed $value): bool {
        // check if is valid
        if($value === 'test'){
            return true;
        }
        
        // This message will be combined with the customrule
        $this->message('Your value must be test');
        
        return false;
    }
}
```

`request()->csrf()` Generates a csrf token that you can validate with `request()->validateCsrf()`. You want to use this with every request to your backend that is not an `GET` request.
```php
<input type="hidden" name="_token" value="<?= request()->csrf(); ?>">
```

`request()->validateCsrf()` Will validate if your `csrf token` is valid.

```php
if(!request()->validateCsrf()){
    throw new \Exception('Your token is not valid!');
}
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

## Querybuilder

### Methods
`logSql()` Logs the query + bindings on the page or inside ray
```php
$db->table('users')->logSql()->where('id', '=', 1);
```

`raw(query: string, bindings: array)`
**When your want to use user input values, you may want to use the bindings parameter**
```php
// without bindings
$db->raw('SELECT * FROM `users` WHERE `users`.`id` = 1');
// with bindings
$db->raw('SELECT * FROM `users` WHERE `users`.`id` = ?', [1]);
```

`table(table: string, columns: ...string|array)` Logs the query + bindings on the page or inside ray
```php
// SELECT * FROM `users`
$db->table('users')->all();
// SELECT * FROM `users` LIMIT 1 OFFSET 0
$db->table('users')->one();
// DELETE FROM `users`
$db->table('users')->delete();
// UPDATE `users` SET ...
$db->table('users')->update(['name' => 'test name']);
```

`select(...string|array)` Will set the select columns, `subQueries`
```php
$db->table('users', 'id')->where('id', '=', 1);
$db->table('users', 'id', 'email')->where('id', '=', 1);
$db->table('users', ['id', 'email'])->where('id', '=', 1);
// OR
$db->table('users')->select('id')->where('id', '=', 1);
$db->table('users')->select('id', 'email')->where('id', '=', 1);
$db->table('users')->select(['id', 'email'])->where('id', '=', 1);

// OR sub select
// SELECT (SELECT count(posts.id) FROM posts WHERE users.id = posts.user_id) as post_count FROM `users`
$db->table('users')->select([
    'post_count' => function(QueryBuilder $query){
        $query->table('posts', 'count(posts.id)')->whereColumn('users.id', '=', 'posts.user_id');
    }
]);
```

`where(column: Closure|string, operator: array|string, value: mixed = null, boolean: string(OR|AND) = 'AND')` Append where statement
```php
// SELECT * FROM `users` WHERE `email` = ? // bindings: ['test@example.com']
$db->table('users')->where('email', '=', 'test@example.com');
$db->table('users')->where('email', 'test@example.com');

// SELECT * FROM `users` WHERE `email` = ? OR `email` = ? // bindings: ['test@example.com', 'test@example.com']
$db->table('users')->where('email', '=', 'test@example.com')->where('email', 'test@example.com', 'OR');

// SELECT * FROM `users` WHERE `email` = ? AND `email` = ? // bindings: ['test@example.com', 'test@example.com']
$db->table('users')->where('email', '=', 'test@example.com')->where('email', 'test@example.com', 'AND');
```

`whereRaw(query: string|closure, bindData: array = [], boolean: string(OR|AND) = 'AND')`
```php
// SELECT * FROM `users` WHERE `users`.`email` LIKE '%test@example.com%'
$db->table('users')->whereRaw('`users`.`email` LIKE %test@example.com%');

// SELECT * FROM `users` WHERE `users`.`email` LIKE ? // bindings: ['test@example.com']
$db->table('users')->whereRaw('`users`.`email` LIKE ?', ['test@example.com']);

// SELECT * FROM `users` WHERE `users`.`email` LIKE ?
$db->table('users')->whereRaw('`users`.`email` LIKE ?', ['test@example.com'])->whereRaw('`users`.`email` LIKE ?', ['test@example.com'], 'AND');
```

`orWhere(column: Closure|string, operator: string|null, value: mixed)` Eloquent version of `where('column', 'operator', 'value', 'OR')`
```php
$db->table('users')->where('users.id', '=', 1)->orWhere('users.email', '=', 'test@example.com');
```

`whereIn(column: string, value: Closure|array, boolean: string(OR|AND) = 'AND')`
```php
// SELECT * FROM `users` WHERE `id` IN (?) // bindings: ['1,2,3,4']
$db->table('users')->whereIn('id', [1,2,3,4]);

// SELECT * FROM `users` WHERE `id` IN (SELECT `user_id` FROM posts)
$db->table('users')->whereIn('id', function(QueryBuilder $query){
    $query->table('posts', 'user_id');
});
```

`whereExists(callback: Closure, boolean: string(OR|AND) = 'AND', not: boolean = false)`
```php
// SELECT * FROM `users` WHERE EXISTS (SELECT `created_at` FROM `posts` WHERE `created_at` > ? AND `users`.`id` = `posts`.`user_id` LIMIT 1 OFFSET 0)
$db->table('users')->whereExists(function(QueryBuilder $query){
    $query->table('posts', 'created_at')->whereColumn('created_at', '>', '2022-01-01')
                                        ->whereColumn('posts.id', '=', 'users.id', 'AND')
                                        ->limit(1);
});
```

`whereNotExists(callback: closure, boolean: string(OR|AND) = 'AND')` Eloquent of `whereExists(callback, 'AND', true)`
```php
// SELECT * FROM `users` WHERE NOT EXISTS (SELECT `created_at` FROM `posts` WHERE `created_at` > ? AND `users`.`id` = `posts`.`user_id` LIMIT 1 OFFSET 0)
$db->table('users')->whereNotExists(function(QueryBuilder $query){
    $query->table('posts', 'created_at')->whereColumn('created_at', '>', '2022-01-01')
                                        ->whereColumn('posts.id', '=', 'users.id', 'AND')
                                        ->limit(1);
});
```

`whereColumn(column: string, operator: string|null, value: string|null, boolean: string(OR|AND) = 'AND')` 
**Make sure that you don't use raw input from a user because the columns will not be escaped!**
```php
// SELECT (SELECT count(id) FROM `posts` WHERE `users`.`id` = `posts`.`user_id`) as post_count FROM `users`
$db->table()->select([
    'post_count' => function(QueryBuilder $query){
        $query->table('posts', 'count(id)')->whereColumn('users.id', '=', 'posts.user_id');
    }
]);
```

`join(table: string, first: Closure|string, first: string|null, operator: string|null, value: string|null, type: string(INNER|LEFT|RIGHT|CROSS) = 'INNER')`
**Make sure that you don't use raw input from a user because the columns will not be escaped! If you want to use values from user input make sure you use `where()` inside the closure(join)**
```php
// SELECT * FROM `users` INNER JOIN `posts` ON `users`.`id` = `posts`.`user_id`
$db->table('users')->join('posts', 'users.id', '=', 'posts.user_id');

// SELECT * FROM `users` INNER JOIN (`posts` ON `users`.`id` = `posts`.`user_id` OR `posts` ON `users`.`id` = `posts`.`user_id`)
$db->table('users')->join('posts', function(JoinClause $join){
    $join->on('users.id', '=', 'posts.user_id')->orOn('users.id', '=', 'posts.user_id');
});

// join with user input
// SELECT * FROM `users` INNER JOIN `posts` ON `users`.`id` = ? // bindings [1]
$db->table('users')->join('posts', function(JoinClause $join){
    $join->where('users.id', '=', 1);
});
```

`leftJoin()` Eloquent of `join('table', 'firstColumn', 'operator', 'secondColumn', 'LEFT')`
```php
// SELECT * FROM `users` LEFT JOIN `posts` ON `users`.`id` = `posts`.`user_id`
$db->table('users')->leftJoin('posts', 'users.id', '=', 'posts.user_id');
```

`rightJoin()` Eloquent of `join('table', 'firstColumn', 'operator', 'secondColumn', 'RIGHT')`
```php
// SELECT * FROM `users` RIGHT JOIN `posts` ON `users`.`id` = `posts`.`user_id`
$db->table('users')->rightJoin('posts', 'users.id', '=', 'posts.user_id');
```

`limit(limit: int)`
```php
// SELECT * FROM `users` LIMIT 50 OFFSET 0
$db->table('users')->limit(50)->all([]);
```

`offset(limit: int)`
```php
// SELECT * FROM `users` LIMIT 50 OFFSET 10
$db->table('users')->limit(50)->limit(10)->all([]);
```

`orderBy(column: string, direction: string(ASC|DESC) = 'ASC')`
```php
$db->table('users')->orderBy('create_at')->all([]);
$db->table('users')->orderBy('create_at', 'ASC')->all([]);
// OR 
$db->table('users')->orderBy('create_at', 'DESC')->all([]);
```

`groupBy(...string)`
```php
// SELECT * FROM `users` GROUP BY `title`
$db->table('posts')->groupBy('title');
// OR
// SELECT * FROM `users` GROUP BY `title`, `user_id`
$db->table('posts')->groupBy('title', 'user_id');
```

`when(when: boolean, callback: Closure)`
```php
$isAdmin = false;
$db->table('posts')->when(!$isAdmin, function(QueryBuilder $query){
    $query->where('user_id', '=', 2);
})->all([]);
```

`paginate(currentPage: int, perPage: int = 15)`
```php
// SELECT * FROM `users` LIMIT 50 OFFSET 0
$pagination = $db->table('users')->paginate(1, 50);

// `$pagination` is structures like this:
[
    'current_page' => 1,
    'first_page' => 1,
    'last_page' => ..,
    'per_page' => 50,
    'total_pages' => .., // number of total pages,
    'total_results' => .., // number of results found
    'next_page' => [
        'exists' => true, // false when there is no next page
        'page' => 2 // the next page number
    ],
    'prev_page' => [
        'exists' => false, // false when there is no previous page
        'page' => 1 // the previous page number
    ],
    'results' => [] // array of results
]
```

`all(fallbackReturnValue: mixed = false, fetchMode: int|null = null)`
```php
// You can use this inside a foreach without using the `all()` method
$db->table('users');
// OR 
$db->table('users')->all();
// OR when query fails return value will be `[]`
$db->table('users')->all([]);

// Fetch mode(default fetch mode: \POD::FETCH_ASSOC)
$db->table('users')->all([], \POD::FETCH_ASSOC | \POD::FETCH_COLUMN);
```

`one(fallbackReturnValue: mixed = false, fetchMode: int|null = null)`
```php
// SELECT * FROM `users` LIMIT 1 OFFSET 0
$db->table('users')->one();
// when query fails return value will be `[]`
$db->table('users')->one([]);

// Fetch mode(default fetch mode: \POD::FETCH_ASSOC)
$db->table('users')->one([], \POD::FETCH_ASSOC | \POD::FETCH_COLUMN);
```

`column(fallbackReturnValue: mixed = false, column: int = 0)`
```php
$userInfo = $db->table('users', 'username', 'email')->limit(1);
// to retrieve `username` use You can event overwrite
$username = $userInfo->column(0);

// to retrieve `email` use
$email = $userInfo->column(1);
```

`insert(insertData: array<string,mixed>)` 
When the query `Failed` then the insert method will return `false` else the method will return `insertId`
```php
$insertId = $db->table('posts')->insert([
    'title' => 'test title',
    'slug' => 'test-title',
    'body' => 'This is an test body'
]);
```

`update(updateData: array<string,mixed>)`
```php
$passed = $db->table('users')->where('id', '=', 1)->update([
    'titel' => 'Update title'
]);
```

`delete()`
```php
$passed = $db->table('users')->where('id', '=', 1)->delete();
```
