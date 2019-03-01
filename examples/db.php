<?php

db\connection(new PDO('sqlite:database.sqlite'));
db\connection()->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
db\connection()->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

$posts = db\table('posts')->get('is_published=?', [1]);
$posts = db\table('posts')->select('title, body')->get();

$post = db\table('posts')->find('id=?', [1]);
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

db\insert('posts', $data);
db\update('posts', $data, 'where id=?', [5]);
db\delete('posts', 'where id=?', [7]);
