# yidemir/mikro
Fast and useful micro application framework

## Installation
```
composer require yidemir/mikro
```

## Features
* String encryption/decryption
* Database functionality (create, read, update, delete, querying)
* Pagination (array pagination)
* Request processiong
* Response methods
* Router (methods, groups, resources)
* Validator (simple validation)
* View (simple layout and blocks)

## Usage
```php
route\get('/', function() {
    return response\view('homepage', ['param' => 'data']);
});

route\post('/save', function() {
    return response\json(['foo' => 'bar']);
});

route\put('/update/:id', function($id) {
    $data = request\input(['title', 'body', 'tags', 'created_at']);
    db\table('posts')->update($data, $id);
    request\flash('post updated!');
    return response\redirect('/');
});

route\group([
    'path' => '/admin',
    'namespace' => 'App\Controllers\Admin\\',
    'middleware' => ['check_admin_middleware']
], function() {
    route\get('/', 'DashboardController@index');
    route\resource('/posts', 'PostController');
    route\resource('/categories', 'CategoryController');
    route\get('/foo', function() {
        return response\html('admin panel');
    });
});

route\error(function() {
    if (request\is_ajax()) {
        return response\json(['foobar' => 'bazqux']);
    } else {
        return response\view('errors/404');
    }
});
```
more examples in examples directory.
