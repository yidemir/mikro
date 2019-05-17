# Introduction
**Mikro** is a small and fast micro PHP framework.

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
