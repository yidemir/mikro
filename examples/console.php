<?php

console\register('foo:action', function(array $args) {
    echo "Foo action trigerred!\n";
});

console\register('action', function(array $args) {
    if (array_key_exists('n', $args)) {
        echo "-n parameter found!\n";
    }

    if (array_key_exists('name', $args)) {
        echo "--name parameter found! Your name is {$args['name']}!\n";
    }

    echo "No parameter found\n";
});

console\register_framework_commands();

if (console\running_on_cli()) {
    echo "Console running in command line interface!";
}

console\run($argv);
