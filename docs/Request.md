# Request
Simple request handler

## Methods

### Method
```php
Request\method(); // example result: 'GET'
```

### Request Path
```php
Request\path(); // example result: '/posts/5'
```

### Request Query String
```php
Request\query_string(); // example result: 'id=5&page=32'
```

### Request Query
```php
Request\query(); // example result: ['id' => 5, 'page' => 32]
```

### Request Parameters
```php
Request\all(); // example result: ['id' => 5, 'page' => 32]
```

### Request Parameter
```php
Request\get('page'); // example result: '32'
// or
Request\input('page'); // example result: '32'
```

### Request Body
Gets request body
```php
Request\content(); // returns request body
```

### Request Body (array)
Gets request body in array
```php
Request\to_array(); // array
```

### Request Header
```php
Request\headers(); // get all headers in array
Request\header('Content-type'); // get Content-Type header
```

### Check Json Request
```php
Request\wants_json(); // bool
```

### Bearer Token
```php
Request\bearer_token(); // bool
```

### Only specific parameters
```php
Request\only(['param1', 'param2']); // array
```

### Except specific parameters
```php
Request\except(['param1', 'param2']); // array
```
