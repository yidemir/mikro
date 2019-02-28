<?php

view\path(__DIR__ . '/views');

view\render('home', ['name' => 'Renas']);

view\start('content');
echo 'content block!';
view\stop();

echo view\block('content');
