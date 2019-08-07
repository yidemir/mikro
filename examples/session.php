<?php

session\set('foo', 'bar');
session\set([
    'foo' => 'bar',
    'baz' => 'qux'
]);

session\all(); // array

session\get('foo'); // bar
session\get('qux', 'default'); // default
