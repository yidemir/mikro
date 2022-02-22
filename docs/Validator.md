# Validator
Simple array validator

## Validation Method
Validator performs validation using PHP callbacks. For example:

```php
$data = ['key' => 'value', 'foo' => 'bar', 'baz' => ''];

Validator\validate($data, 'key', 'isset|!empty'); // returns true, its valid
Validator\validate($data, 'key', ['isset', '!empty']); // returns true, its valid
Validator\validate($data, 'qux', 'isset|!empty'); // returns false, qux key not exists
Validator\validate($data, 'qux', '!empty'); // returns false, qux key not exists
Validator\validate($data, 'baz', '!empty'); // returns false, baz key is empty
Validator\validate($data, 'baz', fn($value, $data) => strlen($value) >= 3);
Validator\validate($data, 'baz', [fn($value, $data) => strlen($value) >= 3]);

$callback = fn($value, $data) => true;
Validator\valdiate(Request\all(), 'username', [$callback, 'ctype_alnum']);
```

**Validate with parameters**
****
```php
Validator\validate(Request\all(), 'email', ['isset', '!empty', 'filter_var:' . FILTER_VALIDATE_EMAIL]);
```

**Validate and get all results in array**
```php
Validator\validate_all(
    ['title' => 'foo', 'email' => 'bar'],
    [
       'title' => 'isset|!empty|ctype_alnum',
       'email' => 'isset|!empty|filter_var:' . FILTER_VALIDATE_EMAIL
   ]
);

// array(2) {
//   ["title"]=> bool(true)
//   ["email"]=> bool(false)
// }
```

```php
Validator\is_validate_all(
    ['title' => 'foo', 'email' => 'bar'],
    [
       'title' => 'isset|!empty|ctype_alnum',
       'email' => 'isset|!empty|filter_var:' . FILTER_VALIDATE_EMAIL
   ]
); // returns bool
```
