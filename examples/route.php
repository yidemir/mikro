<?php

route\get('/', function() {
    return response\html('Hello world!');
});

route\post('/save', 'PostController@save');
route\put('/update/:id', ['PostController', 'update']);
route\delete('/destroy/:id', 'destroy_function');
route\any('/hello', 'HelloController@index');

route\group([
    'path' => '/admin',
    'namespace' => 'App\Controllers\Admin\\',
    'middleware' => []
], function() {
    route\get('/', 'Dashboard@index');
    route\resource('/posts', 'PostController');
});

route\error(function() {
    return response\json(['error' => 404]);
});

