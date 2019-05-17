# Introduction
Mikro a lightweight micro framework

# Installation
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
