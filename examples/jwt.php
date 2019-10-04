<?php

jwt\secret('foobar');

$jwt = jwt\encode([
    'id' => 100,
    'email' => 'foo@bar.net'
]);

$user = jwt\decode($jwt);

$user->id; // 100
$user->email; // foo@bar.net


// verify:
try {
    jwt\decode('foobar');
} catch (Exception $e) {
    echo $e->getMessage();
}

// not verify
jwt\decode('foobar', false);
