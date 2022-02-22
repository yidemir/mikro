Project in development. **Do not use** (yet)

# mikro - micro approach to traditional

[![Latest Version on Packagist](https://img.shields.io/packagist/v/yidemir/mikro.svg?style=flat-square)](https://packagist.org/packages/yidemir/mikro) [![Total Downloads](https://img.shields.io/packagist/dt/yidemir/mikro.svg?style=flat-square)](https://packagist.org/packages/yidemir/mikro) [![License](https://img.shields.io/packagist/l/yidemir/mikro)](https://packagist.org/packages/yidemir/mikro)


This project is a tool developed to solve some tasks and requests with simple methods, rather than a framework.

I tried to take this project, which I started as a hobby, one step further. There have been fundamental changes compared to the previous version.

Available packages/components:
* **Router** - An ultra-simple router with grouping and middleware support.
* **Request** - An easy way to access PHP global request variables.
* **Response** - Sends data/response to the client.
* **DB** - It simplifies your CRUD operations with a PDO instance.
* **Error** - Handle error and exceptions in easy way.
* **Validator** - An ultra-simple validation library that allows you to verify data.
* **View** - A view renderer (PHP files) with simple block support.
* **Auth** - A simple authorization library with JWT.
* **Cache** - It is a simple file-based caching (or key-value) structure. It does not have a expire/timeout feature.
* **Config**  - It is a simple config structure with setter and getter. It supports multi-dimensional arrays with dot notation.
* **Console** - Executes a callback according to the parameter from the command line.
* **Container** - A simple service container.
* **Crypt** - It encrypts and decrypts strings with OpenSSL.
* **Csrf** - It helps you to prevent CSRF deficit for forms.
* **Curl** - It allows you to use the Curl library in a simple way.
* **Event** - A simple event listener and emitter.
* **Flash** - It is a structure that works with PHP session, allows you to move errors and messages between requests.
* **Html** - It provides a class that allows you to generate HTML code.
* **Jwt** - A simple JSON web token authentication structure.
* **Locale** - Multi-language/localization structure based on PHP arrays.
* **Logger** - Basic file logging.
* **Pagination** - Paginate any array data with total item count.

## Installation

You can install the package via composer:

```bash
composer require yidemir/mikro
```

## Usage

```php
require __DIR__ . '/vendor/autoload.php'; // all packages are loaded

// set package settings
$mikro = [
    View\PATH => __DIR__ . '/views',
    DB\CONNECTION => new PDO('sqlite::memory:'),
    Crypt\SECRET => 'your-secret-key',
];

Router\get('/', 'Controllers\HomeController::index');
Router\get('/items/{item:num}', fn() => [new Controllers\HomeController(), 'getItem']());
Router\any('/admin', 'Controllers\Admin\DashboardController::index', [new Middleware\CheckAuth()]);
```

```php
namespace Controllers;

use DB;
use Router;
use Response;

class HomeController
{
    public static function index()
    {
        $data = ['name' => 'Mikro'];

        return Response\view('home', compact('data'));
    }

    public function getItem()
    {
        $itemId = Router\parameters('item');
        $item = DB\table('items')->find($itemId);

        return Response\json(['message' => 'OK', 'data' => $item]);
    }
}
```

```php
namespace Controllers;

use DB;
use Response;

class DashboardController
{
    public static function index()
    {
        $stats = DB\table('stats')->get('order by created_at desc');

        return Response\view('admin/index', compact('stats'));
    }
}
```

```php
namespace Middleware;

use Auth;
use Request;
use Response;
use Closure;

class CheckAuth
{
    public function __invoke(Closure $next)
    {
        if (! Auth\check() && Request\get('key') !== 'secret') {
            return Response\redirect('/to/home');
            // or
            return Response\json(['message' => 'Unauthorized'], 403);
        }

        return $next();
    }
}
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
