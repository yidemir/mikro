<?php

db\connection([
    'default' => new PDO('sqlite:default.sqlite'),
    'secondary' => new PDO('mysql:...')
]);
db\connection()->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // on 'default' named connection
db\connection('secondary')->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

$posts = db\table('posts')->connection('secondary')->get('is_published=?', [1]);
$posts = db\table('posts')->select('title, body')->get(); // on 'secondary' named connection

$post = db\table('posts')->conncetion('default')->find('id=?', [1]);
$post = db\table('posts')->find(1);
$post = db\table('posts', 'another_primary_key')->find(1);

db\table('posts')->insert(['title' => 'foo', 'body' => 'bar']);

db\table('posts')->update($data, 5);
db\table('posts')->update($data, 'where id=? and is_published=?', [5, true]);

db\table('posts')->delete(); // delete all
db\table('posts')->delete(5);
db\table('posts')->delete('where id=?', [5]);

db\table('posts')->paginate('where is_published=?', [1], ['perPage' => 25]);

db\query('select * from posts where is_published=?', [true])->fetchAll();

$post = db\fetch('select * from posts where id=?', [5]);
$post = db\fetch_object('select * from posts where id=?', [5]);
$posts = db\fetch_all('select * from posts');
$posts = db\fetch_all_object('select * from posts');
$count = db\fetch_column('select count(*) from posts');

db\insert('posts', $data);
db\update('posts', $data, 'where id=?', [5]);
db\delete('posts', 'where id=?', [7]);
