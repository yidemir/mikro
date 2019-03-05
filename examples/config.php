<?php

config\collection([
    'foo' => 'bar',
    'bar' => [
        'baz' => 1,
        'qux' => 2
    ]
]);

config\collection(); // get all

config\get('foo'); // bar
config\get('bar.baz'); // 1

config\set('foo', 'test');
config\set('bar.baz', 3);
config\set('bar.foo', 'test');
