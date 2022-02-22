# Router
Router is simple and powerful page handler.

## Basics
Router is under the `Router` namespace. 

Router methods are:
```php
Router\get('/', 'callback');
Router\post('/save', 'callback');
Router\patch('/update', 'callback');
Router\put('/update', 'callback');
Router\delete('/delete', 'callback');
Router\any('/page', 'callback');
```

The `callback` parameter must be contain a PHP callback. Examples:

```php
function foo () {}
is_callable('foo'); // true
Router\get('/', 'foo'); // calls foo function

$foo = function () {};
is_callable($foo); // true
Router\get('/foo', $foo); // calls foo Closure

class Foo
{
    public static function bar() {}
}
is_callable('Foo::bar'); // true
Router\get('/bar', 'Foo::bar'); // calls bar() static method

class Bar
{
    public function baz() {}
    public function __invoke() {}
}
is_callable([new Bar(), 'baz']); // true
is_callable(new Bar()); // true
Router\get('/', [new Bar(), 'baz']); // calls baz() method
Router\get('/', fn() => [new Bar(), 'baz']()); // calls baz() method (correct usage)
Router\get('/', new Bar()); // invoke Bar class
```

## Router Parameters
The Router uses regex strings to map the routes. If you don't want to use Regex strings, you can use ready-made basic patterns.

```php
Router\get('/items/{name}', 'ItemController::show');
Router\put('/events/{id:num}', 'EventController::update');
Router\get('/posts/{slug:str}', 'PostController::show');
```

Available patterns:
```
{parameter} => any string
{parameter:num} => \d+ (digits)
{parameter:str} => [\w\-_]+ (string)
{parameter:any} => [^/]+ (any string, includes special chars, digits etc.)
{parameter:all} => .* (any string on full uri)
```

### Using Route Parameters on Callback
To use the resolved route parameters in the callback, you must use the `parameters()` method. For example:

```php
// index.php

Router\get('/posts/{id}', 'Controllers\PostController::show');
```

```php
// PostController.php

class PostController
{
    public static function show()
    {
        // GET /posts/5
        $allParameters = \Router\parameters(); // ['id' => '5']
        $id = \Router\parameters('id'); // '5'
    }
}
```

## Route Groups
Route Groups, provides grouping according to the specified prefix. 

```php
Router\get('/home', 'HomeController::index');

Router\group('/admin', function () {
    Router\get('', 'Admin\DashboardController::index');
    Router\get('/stats', 'Admin\DashboardController::stats');
    
    // if you want, you can use nested groups or arrow functions
    Router\group('/posts', fn() => [
        Router\get('', 'Admin\PostController::index'),
        Router\get('/{id:num}', 'Admin\PostController::show'),
        Router\get('/create', 'Admin\PostController::create'),
        Router\post('', 'Admin\PostController::store'),
    ]);
});
```

## Middleware Support
The routes and groups includes a simple middleware support.

**Usage**
```php
Router\get('/', 'route_callback', ['middleware_callback']);
```

**Example**
```php
function middleware(\Closure $next)
{
    // things

    return $next();
}

Router\get('/', 'callback', ['middleware']);
```

### Route Group Middleware Example
```php
Router\group('/admin', fn() => [
    Router\get('/dashboard', 'DashboardController::index', ['DashboardMiddleware::handle'])
], ['AdminAuthenticationMiddleware::handle']);
```

## Handle 404
It should be located at the end of the route definitions.

**Usage**
```php
// Router\get(...);
// Router\post(...);
// other route definitions

Router\error('not_found_callback');
```

**Example**
```php
Router\error(function () {
    return Response\json([
        'message' => '404 not found',
        'data' => []
    ]);
});
```
