# Mikro PHP Framework
**Mikro** is a small and fast micro PHP framework.

---

# Installation
You can install with Composer.

```
composer require yidemir/mikro
````

in `index.php` file:

```php
require __DIR__ . '/vendor/autoload.php';

route\get('/', function() {
  return response\html('Hello world!');
});
```

Or you can use it by including the `autload.php` file.

```php
require __DIR__ . '/mikro/autoload.php';

route\any('/', 'HomeController@index');
```

---

# Documentation
  * [Response](#response) 
  * [Request](#request)
  * [Routing](#routing)
  * [View](#view)
  * [Database](#database)
  * [Container](#container)
  * [Config](#config)
  * [Pagination](#pagination)
  * [Validator](#validator)
  * [Caching](#caching)
  * [Encryption](#encryption)
  * [Event Handling](#event-handling)
  * [Language](#language)
  * [Loging](#logging)
  * [HTML](#html)
  * [Console](#console)
  * [Cookie](#cookie)
  * [CSRF](#csrf)
  * [Flash](#flash)
  * [Session](#session)

---

# Response
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

# Request
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

# Routing
The router supports all REST methods and is resourceful. Each route you define is checked when calling.

```php
route\map(array $methods, string $path, $callback, ?string $name, array $middleware = []): void
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
route\get(string $path, $callback, ?string $name = null, array $middleware = []): void
route\post(string $path, $callback, ?string $name = null, array $middleware = []): void
route\put(string $path, $callback, ?string $name = null, array $middleware = []): void
route\delete(string $path, $callback, ?string $name = null, array $middleware = []): void
route\any(string $path, $callback, ?string $name = null, array $middleware = []): void
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
  });
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

route\api_resource('/admin/posts', App\Controllers\Admin\PostController::class);
```

---

## Handling 404

```php
route\error($callback): void
```

```php
route\error('ErrorController@notFound');
route\error(function() {
  return response\view('errors/404');
});
```

## Route Collection and Matching

```php
/** @var array */
$routes = route\collection();

/** @var object */
$matchedRoute = route\resolve();

// runs the matched route
route\run();
```

# View
Before using View, you must specify the directory where the view files are located.

```php
route\path('/path/to/views');
```

---

## Render a view file

```php
view\render(string $file, array $data = []): ?string
```

```php
view\render('viewfile', ['data' => 'value']);
```

---

## View Blocks

`index.php` file:
```html
<?php view\start('content') ?>
  <p>Block content</p>
<?php view\stop() ?>

<?php view\start('scripts') ?>
  <script src="vue.min.js"></script>
<?php view\stop() ?>

<?php echo view\render('layout') ?>
```

`layout.php` file:

```html
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Hello world!</title>
</head>
<body>
  <div id="app" class="continer">
    <?php echo view\block('content') ?>
  </div>
  
  <?php echo view\block('scripts') ?>
</body>
</html>
```

**Set block in one line**

```php
view\set('title', 'Hello framework!');

view\set('callbable_block', function($args) {
  return "<title>$args['title']</title>";
});
```

**Getting block**
```php
echo view\block('title');
echo view\get('title');

echo view\get('callable_block', ['title' => 'Hello World!']);
```

**Parent**
```html
<?php view\start('content') ?>
  <p>Hello world</p>
<?php view\stop() ?>

<?php view\start('content') ?>
  <?php view\parent() ?>
  <p>Second content</p>
<?php view\stop() ?>
```

**Escaping unsafe content**
```html
<input type="text" name="title" value="<?php echo view\e($post->title) ?>">
```

**References**
```php
view\path(?string $path = null): string
view\render(string $file, array $data = []): ?string
view\blocks($name = null, $data = null): array
view\start(?string $name = null): string
view\stop(): void
view\block($name, $default = null): mixed
view\set($name, $value): void
view\get($name, array $args = []): mixed
view\parent(): mixed
view\e($string): string
```

---

# Database
In order to perform database operations, you must first provide a database connection. By default, you need to define a database named 'default'.

```php
db\connection([
  'default' => new PDO('mysql..'),
  'sqlite' => new PDO('sqlite:..')
]);
```

**PDO database connection**
```php
$pdo = db\connection(); // 'default' connection
$pdo = db\connection('sqlite');
```

---

## Table Method

```php
db\table(string $table, string $primaryKey = 'id'): object
db\table('table')->select(string $select): object
db\table('table')->connection(string $name): object
db\table('table')->get(string $queryPart = '', array $params = []): mixed
db\table('table')->find($queryPart = '', array $params = []): mixed
db\table('table')->insert(array $data): PDOStatement
db\table('table')->update(array $data, string $query = '', array $params = []): PDOStatement
db\table('table')->delete($query = '', array $params = []): PDOStatement
db\table('table')->paginate(string $query = '', array $params = [], array $options = []): mixed
```

## Select

```php
db\table('posts')->get(); // all posts
db\table('posts')->find(5); // get post 5
db\table('posts')->get('order by created_at desc');
db\table('posts')->get('where is_approved=1 and type=?', ['post']);
db\table('posts')->select('id, title, body')->get();
db\table('posts', 'post_id')->find(5); // get post_id=5
```

---

## Insert

```php
$data = [
  'title' => 'Lorem lipsum',
  'body' => 'foo bar'
];

// $data = request\input(['title', 'body']);

db\table('posts')->insert($data);

$id = connection()->lastInsertId();
```

---

## Update

```php
$data = ['title' => 'Lorem lipsum dolor sit amet'];

db\table('posts')->update($data, 5);
db\table('posts')->update($data, 'where id=?', [5]);
```

---

## Delete

```php
db\table('posts')->delete(5);
db\table('posts')->delete('where id=?', [5]);
```

---

## Querying

```php
db\query(string $query, array $params = []): PDOStatement
```

```php
db\query('select * from posts')->fetchAll();
db\query('select * from posts where id=?', [$id])->fetch()
```

---

## Fetch Methods

```php
db\fetch('select * from ...');
db\fetch_object('select * from ...');
db\fetch_all('select * from ...');
db\fetch_all_object('select * from ...');
db\fetch_column('select count(*) from ...');
db\exec('query');
```

---

# Container
Container items are stored in the `collection` method.

**New container item**
```php
container\set('item', function() {
  return new stdClass;
});
```

**Check item exists**
```php
if (contaner\has('item')) {
  var_dump(container\get('item'));
}
```

**Get container item**
```php
$service = container\get('foo.service');
```

**New singleton item**
```php
container\singleton('bar.service', function() {
  return new FooBarService;
});
```

---

# Config
Configuration items are stored in the `collection` method.

```php
config\collection(array $configs = []): array
config\get(string $key, $default = null): mixed
config\set(string $key, mixed $value): array
```

**Config collection**
```php
config\collection([
  'site' => [
    'name' => 'Framework',
    'url' => 'http://0.0.0.0:8000'
  ],
  
  'databases' => [
    'default' => new PDO(...),
    'sqlite' => new PDO(...)
  ]
]);
```

**Get config item**
```php
config\get('site.name'); // 'Framework'
config\get('databases'); // Array
config\get('site.foo', 'bar'); // 'bar'
```

**Set config item**
```php
config\set('site.name', 'Hello world');
config\set('foo', 'bar');
```

---

# Pagination

```php
pagination\paginate(array $options): object
pagination\data($data = null): object
```

**Array pagination**
```php
$array = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];

pagination\paginate([
  'total_items' => count($array)
]);

var_dump(pagination\data());
/*
stdClass {
  'currentPage' => 1, 
  'totalPages' => 5,
  'perPage' => 2,
  'start' => 1,
  'limit' => '1,5',
  'pages' => Array
}
*/
```

**Pagination with DB**
```php
$count = db\fetch_column('select count(*) from posts');
$pagination = pagination\paginate([
  'total_items' => $count,
  'current_page' => request\input('page', 1),
  'per_page' => 5,
  'pattern' => '/foo/bar?page_number=:number'
]);

// use on view: $pagination = pagination\data();

$posts = db\fetch_all("select * from posts limit {$pagination->limit}");
```

---

# Validator
Validator performs a simple array validation according to the given rules.

```php
$validator = validator\validate(request\all(), [
  'name' => 'required|minlen:4|maxlen:255',
  'email' => 'required|email',
  'amount' => 'required|numeric',
  'website' => 'nullable|url',
  'age' => 'nullable|min:18'
]);

$validator->success; // boolean
$validator->fails; // boolean
$validator->values; // array, passed values in array
$validator->errors; // error messages array
$validator->errorByFields; // error by fields array
```

**Add new rule**
```php
validator\collection('in_any_case_passes', function($key, $values, $param = null) {
  return true;
});

validator\message('in_any_case_passes', 'No, that\'s not possible');

validator\validate([/* data */], [
  'key' => 'required|in_any_case_passes'
]);
```

**References**
```php
collection(?string $rule = null, ?Closure $callback = null): array
messages(?string $rule = null, ?string $message = null): array
validate(array $values, array $rules): stdClass
```

---

# Caching

**References**
```php
path(?string $path = null): string
get(string $key, $default = null)
set(string $key, $value, $ttl = 0): void
remove(string $key): void
has(string $key): bool
flush(): void
remember(string $key, Closure $callback, $ttl = 0)
```

---

# Encryption

**References**
```php
secret(?string $secret = null): string
encrypt(string $data): string
decrypt(string $data)
```

---

# Event Handling
**References**
```php
listen(?string $name = null, ?Closure $callback = null)
emit(string $name, array $args = [])
```

# Language
**References**
```php
path(?string $path = null): string
lang(?string $code = null)
get(string $key, $default = null)
phrase(string $phrase, ?string $file = null)
```

---

# Logging
**References**
```php
path(?string $path = null): string
write(string $type, $data)
error($data)
info($data)
debug($data)
```

---

# Html
The HTML tool allows you to create simple form interfaces and HTML tags. It is not used for DOM manipulation.

**References**
```php
tag(string $name, string|array $content = '', array $attributes = []): object
div(string|array $content = '', array $attributes = []): object
p(array|string $content = '', array $attributes = []): object
span(array|string $content = '', array $attributes = []): object
label(array|string $content = '', ?string $for = null, array $attributes = []): object
input(string $type, ?string $name = null, array $attributes = []): object
textarea(array|string $content = '', ?string $name = null, array $attributes = []): object
select(array $options = [], ?string $name = null, array $attributes = []): object
button(array|string $content = '', array $attributes = []): object
```

**Creating HTML tags**
```php
html\tag('div', 'Hello world!', ['class' => 'alert alert-success']);
// or
html\tag('div', 'Hello world!')->class('alert alert-success');
// returns <div class="alert alert-success">Hello world!</div>

html\tag('div', [
  html\tag('label', 'Username')->for('username'),
  html\tag('input')
    ->type('text')
    ->name('username')
    ->id('username')
    ->class('form-control')
])->class('form-group');

// returns
// <div class="form-group">
// <label for="username">Username</label>
// <input type="text" name="username" id="username" class="form-control">
// </div>

// or
html\div([
  html\label('Username', 'username'),
  html\input('text', 'username')->class('form-control')
])->class('form-group')
```

**Form Select Element**
```php
$options = ['' => 'Please Select', 1 => 'One', 2 => 'Two'];

html\select($options, 'category_id');
// returns
// <select name="category_id" id="category_id">
// <option value="">Please Select</option>
// <option value="1">One</option>
// <option value="2">Two</option>
// </select>

// If we want to change the attributes of the option element:
html\select($options, 'category_id', [
  'selectedOption' => 1,
  'optionAttributes' => [
    1 => ['data-foo' => 'bar'],
    2 => ['x' => 'y']
  ],
  'class' => 'form-control'
])->onclick("alert('Select clicked')")

// returns
// <select name="category_id" id="category_id" class="form-control" onclick="alert('Select clicked')">
// <option value="">Please Select</option>
// <option value="1" selected data-foo="bar">One</option>
// <option value="2" x="y">Two</option>
// </select>
```

---

# Console
**References**
```php
collection(array $command = [])
register(string $command, Closure $callback)
run(array $argv)
running_on_cli(): bool
register_framework_commands()
```

---

# Cookie
**References**
```php
disable_encryption(?bool $is = null): bool
get(string $key, $default = null)
set(
    $key,
    $value,
    $expires = 0,
    string $path = '',
    string $domain = '',
    bool $secure = false,
    bool $httpOnly = false
): bool
```

---

# CSRF
**References**
```php
token(): string
field(): string
check(?string $token = null)
```

---

# Flash
**References**
```php
push(string $message, string $type = 'default')
message(string $message)
error(string $message)
get(string $type = 'default'): array
```

---

# Session
**References**
```php
get(string $key, $default = null)
set($key, $value = null)
all(): array
```
