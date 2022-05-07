Project in development. **Do not use** (yet)

# mikro - micro approach to traditional

[![Latest Version on Packagist](https://img.shields.io/packagist/v/yidemir/mikro.svg?style=flat-square)](https://packagist.org/packages/yidemir/mikro) [![Total Downloads](https://img.shields.io/packagist/dt/yidemir/mikro.svg?style=flat-square)](https://packagist.org/packages/yidemir/mikro) [![License](https://img.shields.io/packagist/l/yidemir/mikro)](https://packagist.org/packages/yidemir/mikro)

This project is a tool developed to solve some tasks and requests with simple methods, rather than a framework.

I tried to take this project, which I started as a hobby, one step further. There have been fundamental changes compared to the previous version.

Available packages:
* **Cache** - It is a simple caching structure.
* **Config**  - It is a simple config structure with setter and getter. It supports multi-dimensional arrays with dot notation.
* **Console** - Executes a callback according to the parameter from the command line.
* **Container** - A simple service container.
* **Crypt** - It encrypts and decrypts strings with OpenSSL.
* **DB** - It simplifies your CRUD operations with a PDO instance.
* **Event** - A simple event listener and emitter.
* **Helper** - String and array helpers and more
* **Jwt** - A simple JSON web token authentication structure.
* **Locale** - Multi-language/localization structure
* **Logger** - Basic logging
* **Request** - An easy way to access PHP global request variables.
* **Response** - Sends data/response to the client.
* **Router** - An ultra-simple router with grouping and middleware support.
* **Validator** - A validation library that allows you to verify data and in doing so you can only use functions in the PHP library or callbacks that you have prepared yourself.
* **View** - A view renderer with block and template support.

## Installation

You can install the package via composer:

```bash
composer require yidemir/mikro
```

## Usage

**Routing**
``` php
Router\get('/', fn() => Response\view('home'));
```

```php
Router\group('/admin', fn() => [
    Router\get('/', 'DashboardController::index'),
    Router\resource('/posts', PostController::class),
    Router\get('/service/status', fn() => Response\json(['status' => true], 200)
], ['AdminMiddleware::handle']);

Router\files('/', __DIR__ . '/sync-directory');
```

```php
Router\error(fn() => Response\html('Default 404 error', 404));
```

**Database**
```php
DB\model('products', [
    // definitions
    'table' => 'products',
    'fillable' => ['title', 'description', 'price'],

    // getters and setters
    'get_title' => fn($title) => strtoupper($title),
    'get_price' => fn($price) => Html\tag('span', $price)->class('price'),
    'set_description' => fn($description) => Helper\str($description)->upper(),

    // events
    'event_created' => fn(array $attributes) => Event\emit('product.created', [$attributes]),
    'updating_event' => fn(array $attributes) => isset($attributes['key']),

    // custom methods
    'getActiveCount' => fn() => $this->where('status=?', [true])->count()
]);

DB\table('products')->fill(['title' => 'Product title'])->insert();
DB\table('products')->fill(['title' => 'New product title'])->where('id=5')->update();
$products = DB\table('products')->where('status=?', [true])->paginate();
$count = DB\table('products')->getActiveCount();
```

**View and Templates**
```html
@View\set('title', 'Page title!');

@View\start('content');
    <p>Secure print: @=$message; or unsecure print @echo $message;</p>
@View\stop();

@View\start('scripts');
    <script src="app.js"></script>
@View\push();

@echo View\render('layout');
```

```html
<!-- layout.php -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@View\get('title', 'Hey!');</title>
</head>
<body>
    @View\get('content');

    @View\get('scripts');
</body>
</html>
```

All methods and constants are documented at the source. The general documentation will be published soon.

### Testing

```bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email demiriy@gmail.com instead of using the issue tracker.

## Credits

- [Yılmaz Demir](https://github.com/yidemir)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
