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
