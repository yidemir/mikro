<?php

cache\path(__DIR__); // set cache file directory

cache\get('foo'); // null
cache\get('foo', 'bar'); // 'bar'

cache\set('foo', [1, 2, 3]);
cache\get('foo'); // [1, 2, 3]

cache\set('bar', 'qux', '1 day'); // cache 1 day
cache\set('bar', 'qux', 84600*2); // cache 2 days
cache\set('bar', 'qux', \time() + 1); // cache 1 second
cache\set('bar', 'qux'); // cache with no time limit

$posts = cache\remember('posts', function() {
    return db\table('posts')->get();
});

$posts === cache\get('posts'); // true

$items = cache\remember('items', function() {
    return [1,2,3];
}, '2 weeks');
