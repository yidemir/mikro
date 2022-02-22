<?php

declare(strict_types=1);

namespace Mikro\Tests;

use PHPUnit\Framework\TestCase;

class AuthTest extends TestCase
{
    public function setUp(): void
    {
        global $mikro;

        $mikro = [
            \Auth\TABLE => 'mikro_test_users',
            \Auth\EXPIRATION => 2, // 2 second expire
            \DB\CONNECTION => new \PDO('sqlite::memory:'),
            \Jwt\SECRET => str_repeat(uniqid(), 2)
        ];
        $mikro[\DB\CONNECTION]->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $this->assertSame(\Auth\migrate(), 0);
    }

    public function testNotLoggedIn()
    {
        $this->assertFalse(\Auth\check());
        $this->assertNull(\Auth\user());
        $this->assertFalse(\Auth\can('any'));
        $this->assertEmpty(\Auth\abilities());
        $this->assertSame(\Auth\type(), 'default');
        $this->assertFalse(\Auth\login('foo', 'bar'));
    }

    public function testAuthRegisterMethod()
    {
        $this->assertFalse(\Auth\register([
            'email' => 'text'
        ]));

        $this->assertFalse(\Auth\register([
            'email' => 'foo@bar.baz',
            'password' => ''
        ]));

        $this->assertFalse(\Auth\register([
            'email' => 'foo@bar.baz',
            'password' => 'secret',
            'name' => ''
        ]));

        $this->assertTrue(\Auth\register([
            'email' => 'foo@bar.baz',
            'password' => 'secret',
            'name' => 'name',
            'abilities' => json_encode(['foo.show', 'foo.update'])
        ]));

        $this->assertTrue(\Auth\register([
            'email' => 'admin@admin.com',
            'password' => 'secret',
            'name' => 'admin',
            'abilities' => json_encode(['*']),
            'type' => 'admin',
        ]));
    }

    private function createUsers()
    {
        \Auth\register([
            'email' => 'foo@bar.baz',
            'password' => 'secret',
            'name' => 'name',
            'abilities' => json_encode(['foo.show', 'foo.update'])
        ]);

        \Auth\register([
            'email' => 'admin@admin.com',
            'password' => 'secret',
            'name' => 'admin',
            'abilities' => json_encode(['*']),
            'type' => 'admin',
        ]);
    }

    public function testAuthLoginMethod()
    {
        global $mikro;

        $this->createUsers();
        $this->assertFalse(\Auth\login('foo@bar.baz', 'wrong password', false));
        $this->assertTrue(\Auth\login('foo@bar.baz', 'secret'));
    }

    public function testAuthLoginWithWrongType()
    {
        $this->createUsers();
        \Auth\type('wrong type');
        $this->assertFalse(\Auth\login('foo@bar.baz', 'secret', false));
    }

    public function testAuthLoginSuccess()
    {
        $this->createUsers();
        \Auth\type('default');
        $this->assertTrue(\Auth\login('foo@bar.baz', 'secret', false));
    }

    public function testAuthCheckAndUser()
    {
        $this->createUsers();
        \Auth\login('foo@bar.baz', 'secret', false);
        $this->assertTrue(\Auth\check());
        $this->assertTrue(is_object(\Auth\user()));
        $this->assertSame('foo@bar.baz', \Auth\user()->email);
        $this->assertNotSame('secret', \Auth\user()->password);
    }

    public function testAuthUserType()
    {
        $this->createUsers();
        \Auth\type('admin');
        $this->assertFalse(\Auth\login('foo@bar.baz', 'secret', false));
        $this->assertTrue(\Auth\login('admin@admin.com', 'secret', false));
    }

    public function tearDown(): void
    {
        global $mikro;

        unset($mikro[\Jwt\SECRET]);
    }
}
