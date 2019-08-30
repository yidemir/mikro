<?php

view\path(__DIR__ . '/views');
view\path(['secondary' => __DIR__ . '/secondary/view/files']);

view\render('home', ['name' => 'Renas']); // rendered file: /views/home.php
view\render('secondary:home'); // rendered file: /secondary/view/files/home.php

view\start('content');
echo 'content block!';
view\stop();

view\start('content');
echo view\parent();
echo 'second content block!';
view\stop();

echo view\block('content');

view\set('key', 'value');
view\get('key'); // "value"

view\set('input', function($args) {
    return sprintf('<input type="text" name="%s">', $args['name']);
});

view\get('input', ['name' => 'title']); // '<input type="text" name="title">'

view\e('<script>'); //"&#x3C;script&#x3E;"
