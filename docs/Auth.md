# Auth

Initialize:
```php
$mikro[Auth\TABLE] = 'table-name'; // default: users
$mikro[Auth\EXPIRATION] = 86400; // default: 86400
$mikro[Auth\ISSUER] = 'brand-name'; // default: mikro_auth
```

## Migration
Creates tables in the database. (MySQL and SQLite compatible)
```php
Auth\migrate();
```

## Register
The new user registers. The entered data is validated and the password is encrypted.
```php
$register = Auth\register([
    'name' => 'Name', // mandatory
    'email' => 'e@ma.il', // mandatory
    'password' => 'secret', // mandatory
    'abilities' => json_encode(['items.show', 'items.create']), // optional
]); // returns bool

if ($register) {
    // ok
}
```

## Login
Makes user login. If the third parameter is true (it is default), it creates a cookie.
```php
Auth\login('e@ma.il', 'secret');
Auth\login('e@ma.il', 'secret', false);
```

## Check Login
Checks login
```php
Auth\check(); // bool
```

## Get Logged In User
Returns the user who is logged in.
```php
$user = Auth\user(); // returns ?object

if ($user) {
    $user->email;
    $user->name;
}
```

## Check User Permissions
User permissions are in the `abilities` method. The `can` method is used to check user permissions.
```php
Auth\abilities(); // ['items.show', 'items.create']
Auth\can('items.show'); // bool
Auth\can('items.show|items.create'); // bool
Auth\can(['items.show', 'items.create']); // bool
Auth\can('items.show&items.create'); // bool
```

## User Roles/Types
The default user role is `default` unless otherwise specified. If you have declared a role using the `type` method, registration takes place through this role. In the same way, the login and login checks are also performed through this role.
```php
Auth\type('admin');
Auth\register([]);
Auth\login('...');
Auth\check();
Auth\check('admin');
```

## Logout
```php
Auth\logout();
```
