<?php

container\collection([
    'initial_value_first' => 'foo',
    'value_second' => 'bar'
]);

container\get('value_second'); // 'bar'

container\set('value_third', function() {
    return 'baz';
});

container\get('value_third'); // baz

container\set('object', function() {
    return new \stdClass;
});

container\get('object'); // stdClass instance
container\get('object') === container\get('object'); // false


container\singleton('object_two', function() {
    return new class {
        public function sayHello(string $name)
        {
            return print sprintf('Hello %s!', $name) ;
        }
    };
});

container\get('object_two') === container\get('object_two'); // true

$object = container\get('object_two');
$object->sayHello('Ali'); // prints 'Hello Ali!'

container\has('object'); // true
container\has('object_two'); // true
container\has('object_three'); // false
