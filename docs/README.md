# Introduction
**Mikro** is a small and fast micro PHP framework.
---
# Installation
You can install with Composer.
```
composer require yidemir/mikro
````

in `index.php` file:
```php
<?php

require __DIR__ . '/vendor/autoload.php';

route\get('/', function() {
  return response\html('Hello world!');
});
```

Or you can use it by including the `autload.php` file.
```php
<?php

require __DIR__ . '/mikro/autoload.php';

route\any('/', 'HomeController@index');
```
---
# Responses
There are four defined response types. The `output` method can be used for specific responses.

## HTML Response
```php
response\html(string $content, int $code = 200, array $headers = []): int
```
```php
response\html('<b>HTML Content</b>');
```
---
## JSON Response
```php
response\json(mixed $content, int $code = 200, array $headers = []): int
```
```php
response\json(['message' => 'Ok']);
```
---
## View Response
Refer to section View before using this method.

```php
response\view(string $file, array $data = [], int $code = 200, array $headers = []): int
```
```php
response\view('index', ['foo' => 'bar']);
```
---
## Redirect Response
```php
response\redirect(string $to, int $code = 301): void
```
```php
response\redirect('/foo/bar/url');
```
---
## Specified Response
```php
response\output(string $content, int $code = 200, array $headers = []): int
```
```php
response\output('Text content', 200, ['Content-Type' => 'text/plain']);
```
---
## Headers
```php
response\send_header(string $key, string $value, ...$args): void
```
```php
response\send_header('Content-Type', 'text/plain');
```
---
# Requests
The methods in this section make it easy to process incoming request parameters and headers.

**Retrieving request method:**
```php
request\method(): string
```

**Retrieving request path:**
```php
request\path(): string
```

**Retrieving all headers:**
```php
request\headers(): array
```

**Retrieving header:**
```php
request\get_header(string $key, $default = null)
```

**Check request is ajax**
```php
request\is_ajax(): bool
```
---
## Request Parameters
**Retrieving all request parameters:***
```php
request\all(): array
```

**Retrieving request parameter:**
```php
request\input(mixed $key, mixed $default = null): mixed
```
```php
$id = request\input('id');
$page = request\input('page', 1); // if page parameter not exists, page is 1
$fields = request\input(['title', 'body', 'tags', 'created_at']);
```
---
## Sessions
```php
request\session(mixed $key, mixed $default = null): mixed
```
---
## Flash Messages
```php
request\flash(string $message, string $type = 'default'): void
request\get_flash(string $type = 'default'): array
```
---
## CSRF Protection
Refer to section Crypt before using this methods.
```php
request\get_csrf(): string
request\check_csrf(string $token): bool
```
---
# Routing
The router supports all REST methods and is resourceful. Each route you define is checked when calling.

```php
route\map(array $methods, string $path, $callback, array $middleware = []): void
```
```php
route\map(['GET'], '/', 'HomeController@index');

route\map(['GET', 'POST'], '/test', function() {
  return response\json(['msg' => 'Hello world!']);
});
```
---
## Route Methods
```php
route\get(string $path, $callback, array $middleware = []): void
route\post(string $path, $callback, array $middleware = []): void
route\put(string $path, $callback, array $middleware = []): void
route\delete(string $path, $callback, array $middleware = []): void
route\any(string $path, $callback, array $middleware = []): void
```

```php
route\any('/', function() {
  return response\html('Hello world');
});

route\get('/', 'HomeController@index');
```
---
## Route Groups
```php
route\group($options = null, ?Closure $callback = null): void
```
```php
route\group(['namespace' => 'App\Controllers\\'], function() {
  route\get('/', 'HomeController@index');
  // ... route definitions
  route\group(['path' => '/admin', 'namespace' => 'Admin\\'], function() {
    route\get('/', 'DashboardController@index');
  }, ['middleware_callback', new MiddlewareCallback]);
});

// or simple

route\group('/simple', function() {
  route\get('/', 'SimpleController@foo'); // matches /simple
  route\get('/hard', 'SimpleController@hard'); // matches /simple/hard
});
```
---
## Resourceful Routes
```php
route\resource(string $path, $class, array $middleware = []): void
route\api_resource(string $path, $class, array $middleware = []): void
```
```php
route\resource('/posts', 'App\Controllers\PostController');
route\resource('/categories', App\Controllers\CategoryController::class);

route\api_resource('/admin/posts', App\Controllers\Admin\PostController::class');
```
---
## Handle 404
```php
route\error($callback): void
```
```php
route\error('ErrorController@notFound');
route\error(function() {
  return response\view('errors/404');
});
```
