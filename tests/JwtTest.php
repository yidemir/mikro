<?php

declare(strict_types=1);

namespace Mikro\Tests;

use PHPUnit\Framework\TestCase;

class JwtTest extends TestCase
{
    public function testSecretKeyPresent()
    {
        global $mikro;

        unset($mikro[\Crypt\SECRET]);

        $this->expectException(\Exception::class);

        \Jwt\secret();

        $mikro[\Crypt\SECRET] = str_repeat(uniqid(), 2);

        $this->assertEquals($mikro[\Crypt\SECRET], \Jwt\secret());
    }

    public function testEncodeMethod()
    {
        global $mikro;

        $mikro[\Crypt\SECRET] = str_repeat(uniqid(), 2);

        $this->assertTrue(is_string(\Jwt\encode(['foo' => 'bar'])));
    }

    public function testCreateMethod()
    {
        global $mikro;

        $mikro[\Crypt\SECRET] = str_repeat(uniqid(), 2);

        $this->assertTrue(is_string(\Jwt\create('userid', time() + 60, 'system')));
    }

    public function testDecodeMethod()
    {
        global $mikro;

        $mikro[\Crypt\SECRET] = str_repeat(uniqid(), 2);

        $token = \Jwt\create('userid', $time = time() + 60, 'system');

        $object = \Jwt\decode($token);

        $this->assertEquals('userid', $object->uid);
        $this->assertEquals('system', $object->iss);
        $this->assertEquals($time, $object->exp);
    }

    public function testTokenIsValidAndExpired()
    {
        global $mikro;

        $mikro[\Crypt\SECRET] = str_repeat(uniqid(), 2);

        $token = \Jwt\create('userid', $time = time() + 60, 'system');

        $this->assertTrue(\Jwt\validate($token));
        $this->assertFalse(\Jwt\validate($token . 'foo'));
        $this->assertFalse(\Jwt\expired($token));
        $this->assertFalse(\Jwt\expired($token . 'foo'));

        $token = \Jwt\create('userid', $time = time() - 1, 'system');

        $this->assertTrue(\Jwt\expired($token));
    }
}
