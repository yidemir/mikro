<?php

declare(strict_types=1);

namespace Mikro\Tests;

use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    public function testMapMethod()
    {
        global $mikro;

        $_SERVER['REQUEST_URI'] = '/';

        ob_start();
        \Router\map('GET', '/', fn() => print('Hello'));
        $this->assertEquals(ob_get_clean(), 'Hello');

        $this->assertTrue(\Router\is_found());

        unset($mikro[\Router\FOUND]);
    }

    public function testGetMethodAndNotFound()
    {
        \Router\get('/api', fn() => null);

        $this->assertFalse(\Router\is_found());
    }

    public function testFooRoute()
    {
        global $mikro;

        $_SERVER['REQUEST_URI'] = '/foo';

        ob_start();
        \Router\map('GET', '/foo', fn() => print('Foo'));
        $this->assertEquals(ob_get_clean(), 'Foo');

        $this->assertTrue(\Router\is_found());

        unset($mikro[\Router\FOUND]);
    }

    public function testMiddleware()
    {
        global $mikro;

        $_SERVER['REQUEST_URI'] = '/';

        ob_start();
        \Router\map(
            'GET',
            '/',
            fn() => print('Hello'),
            [fn(\Closure $next) => print('Foo')]
        );

        $this->assertEquals(ob_get_clean(), 'Foo');
        $this->assertTrue(\Router\is_found());

        unset($mikro[\Router\FOUND]);

        ob_start();
        \Router\map(
            'GET',
            '/',
            fn() => print('Hello'),
            [fn($next) => $next()]
        );

        $this->assertEquals(ob_get_clean(), 'Hello');
        $this->assertTrue(\Router\is_found());

        unset($mikro[\Router\FOUND]);
    }

    public function testGroupRoute()
    {
        global $mikro;

        $_SERVER['REQUEST_URI'] = '/admin';

        ob_start();
        \Router\group('/admin', function () {
            \Router\get('', fn() => print('Hello'));
        });
        $this->assertEquals(ob_get_clean(), 'Hello');

        $this->assertTrue(\Router\is_found());

        unset($mikro[\Router\FOUND]);
    }

    public function testTwoGroupRoute()
    {
        global $mikro;

        $_SERVER['REQUEST_URI'] = '/admin/posts';

        ob_start();
        \Router\group('/admin', function () {
            \Router\group('/posts', function () {
                \Router\get('', fn() => print('Hello'));
            });
        });
        $this->assertEquals(ob_get_clean(), 'Hello');

        $this->assertTrue(\Router\is_found());

        unset($mikro[\Router\FOUND]);
    }

    public function testMiddlewareOnGroupRoute()
    {
        global $mikro;

        $_SERVER['REQUEST_URI'] = '/admin';

        ob_start();
        \Router\group(
            '/admin',
            fn() => \Router\get('', fn() => print('Admin')),
            [fn(\Closure $next) => print('Foo')]
        );
        $this->assertEquals(ob_get_clean(), 'Foo');

        $this->assertTrue(\Router\is_found());

        unset($mikro[\Router\FOUND]);
    }

    public function testRouteWithParameter()
    {
        global $mikro;

        $_SERVER['REQUEST_URI'] = '/posts/post-slug';

        ob_start();
        \Router\map('GET', '/posts/{post}', fn() => print(\Router\parameters('post')));
        $this->assertEquals(ob_get_clean(), 'post-slug');

        $this->assertTrue(\Router\is_found());

        unset($mikro[\Router\FOUND]);

        ob_start();
        \Router\map('GET', '/posts/{post:str}', fn() => print(\Router\parameters('post')));
        $this->assertEquals(ob_get_clean(), 'post-slug');

        $this->assertTrue(\Router\is_found());

        unset($mikro[\Router\FOUND]);

        ob_start();
        \Router\map('GET', '/posts/{post:any}', fn() => print(\Router\parameters('post')));
        $this->assertEquals(ob_get_clean(), 'post-slug');

        $this->assertTrue(\Router\is_found());

        unset($mikro[\Router\FOUND]);

        ob_start();
        \Router\map('GET', '/posts/{post:all}', fn() => print(\Router\parameters('post')));
        $this->assertEquals(ob_get_clean(), 'post-slug');

        $this->assertTrue(\Router\is_found());

        unset($mikro[\Router\FOUND]);

        ob_start();
        \Router\map('GET', '/posts/{post:num}', fn() => print(\Router\parameters('post')));
        $this->assertEquals(ob_get_clean(), '');

        $this->assertFalse(\Router\is_found());

        unset($mikro[\Router\FOUND]);

        $_SERVER['REQUEST_URI'] = '/posts/5';

        ob_start();
        \Router\map('GET', '/posts/{post:num}', fn() => print(\Router\parameters('post')));
        $this->assertEquals(ob_get_clean(), '5');

        $this->assertTrue(\Router\is_found());

        unset($mikro[\Router\FOUND]);
    }
}
