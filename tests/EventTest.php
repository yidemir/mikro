<?php

namespace Mikro\Tests;

use PHPUnit\Framework\TestCase;

class EventTest extends TestCase
{
    public function testListenAndEmitMethods()
    {
        \Event\listen('login', fn($user) => print($user . ' logged in!'));

        ob_start();
        \Event\emit('login', ['Renas']);
        $this->assertEquals(ob_get_clean(), 'Renas logged in!');

        \Event\listen('foo', fn() => print(1));
        \Event\listen('foo', fn() => print(2));

        ob_start();
        \Event\emit('foo');
        $this->assertEquals(ob_get_clean(), '12');
    }
}
