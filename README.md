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
* Request processing
* Response methods
* Router (methods, groups, resources)
* Validator (simple validation)
* View (simple layout and blocks)
* Session, Cookie, CSRF and Flash message components
* Logging, event handling and HTML generation components
* and much more

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
    flash\push('post updated!');
    
    return response\redirect('/');
});

route\group([
    'path' => '/admin',
    'namespace' => 'App\Controllers\Admin\\',
    'middleware' => ['App\Middlewares\CheckAdmin'],
    'name' => 'admin.'
], function() {
    route\get('/', 'DashboardController@index', 'home');

    route\resource('/posts', 'PostController');

    route\resource('/categories', 'CategoryController', [
        'only' => 'index|show|create|store'
    ]);

    route\resource('/items', new class {
        public function index()
        {
            $items = db\table('items')->get();

            return response\view('items.index', compact('items'));
        }

        public function show($id)
        {
            $item = db\table('items')->find($id);

            return response\view('items.show', compact('item'));
        }
    });

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

route\run();
```

Database examples
```php
$items = db\table('items')->get('where weight=? order by created_at desc', [37]);

$item = db\table('items')->find('where is_new=:is_new and foo=:foo', [
    'is_new' => true,
    'foo' => 'bar'
]);

$itemCount = db\table('items')->select('count(*)')->column('where foo=?', ['bar']);

$items = db\query('select * from items')->fetchAll();

$item = db\query('select * from posts where id=?', [100])->fetch();

$itemCount = db\query('select count(*) from items')->fetchColumn();
```
more examples in examples directory.
