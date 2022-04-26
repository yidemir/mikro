# Response
Response handler

## HTML Response
Prints and returns HTML response
```php
Response\html('<html>...');

// Response\html(content: 'string', code: 200, headers: []): int
```

## JSON Response
Prints and returns JSON response
```php
Response\json(['message' => 'OK']);

// Response\json(content: ['message' => 'OK'], code: 200, headers: []): int
```

## Plain-text Response
Prints and returns plain text response
```php
Response\text('plain text');

// Response\text(content: 'string', code: 200, headers: []): int
```

## Rendered View Response
```php
Response\view('view-file', ['parameter' => 'value']);

// Response\view(file: 'view-file', data: [], code: 200, headers: []): int
```

## Redirects
```php
Response\redirect('/to-path');
Response\redirect('https://to-url.com');
Response\redirect_back();
```

## Success Response
```php
Response\ok('html response');
Response\ok(['message' => 'json respnose']);

// Response\ok(data: 'mixed', code: 200, headers: []): int
```

## Error Response
```php
Response\error('html response'); // 500 server error
Response\error(['message' => 'json respnose']); // 500 server error

Response\error('404 page not found', Response\STATUS['HTTP_NOT_FOUND']);
Response\error(['message' => '404 not found'], 404);

// Response\error(data: 'mixed', code: 500, headers: []): int
```
