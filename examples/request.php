<?php

request\method();
request\path();

$fields = request\input(['title', 'body', 'name']);
$field = request\input('name', 'default value');

request\is_ajax();

session_start();

request\session('session_key', 'default value');
request\session(['set key' => 'value']);

request\flash('Flash message', 'type');
$messages = request\get_flash();

$token = request\get_csrf();
request\check_csrf($token);

$headers = request\headers();
$header = request\get_header('Foo');
