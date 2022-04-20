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
Router\redirect('/page', '/to');
Router\view('/path', 'viewfile');
```

The `callback` parameter must be contain a PHP [callable](https://www.php.net/manual/en/language.types.callable.php).

### File Based Routing
Converts files in the path and directory specified using the `RecursiveDirectoryIterator` to the route. For example:

```php
Router\files('/', __DIR__ . '/pages');
```

| File Path | Router Equivalent |
|--|--|
| `/pages/index.php` | `Router\get('/', 'callback');` |
| `/pages/filename.php` | `Router\get('/filename', 'callback');` |
| `/pages/index.post.php` | `Router\post('/', 'callback');` |
| `/pages/index.put.php` | `Router\put('/', 'callback');` |
| `/pages/index.patch.php` | `Router\patch('/', 'callback');` |
| `/pages/index.options.php` | `Router\options('/', 'callback');` |
| `/pages/index.delete.php` | `Router\delete('/', 'callback');` |
| `/pages/filename.post.php` | `Router\post('/filename', 'callback');` |
| `/pages/with-{param}.php` | `Router\get('/with-{param}', 'callback');` |
| `/pages/with-{param}.post.php` | `Router\post('/with-{param}', 'callback');` |
| `/pages/items/index.php` | `Router\get('/items', 'callback');` |
| `/pages/items/index.post.php` | `Router\post('/items', 'callback');` |
| `/pages/items/{id}.php` | `Router\get('/items/{id}', 'callback');` |
| `/pages/items/{id}.put.php` | `Router\put('/items/{id}', 'callback');` |

## Route Parameters
The Router uses regular expressions to map the routes. If you don't want to use Regex strings, you can use patterns.

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

### Getting Route Parameters
To use the resolved route parameters in the callback, you must use the `parameters()` method. For example:

```php
Router\get('/posts/{slug}', function () {
    $slug = Router\parameters('slug');
});
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

## Middleware
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

### Route Group Middleware
```php
Router\group('/admin', fn() => [
    Router\get('/dashboard', 'DashboardController::index', ['DashboardMiddleware::handle'])
], ['AdminAuthenticationMiddleware::handle']);
```

## Handle 404
It should be located at the end of the route definitions.

**Usage**
```php
Router\error(function () {
    return Response\html('404 page not found');
});
```

