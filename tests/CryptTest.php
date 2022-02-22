<?php

declare(strict_types=1);

namespace Mikro\Tests;

use PHPUnit\Framework\TestCase;

class CryptTest extends TestCase
{
    public function setUp(): void
    {
        global $mikro;

        $mikro[\Crypt\SECRET] = str_repeat(uniqid(), 3);
    }

    public function testCryptSecretIsPresent()
    {
        $this->assertTrue(is_string(\Crypt\secret()));
    }

    public function testEncryptString()
    {
        $this->assertTrue(is_string(\Crypt\encrypt(uniqid())));
    }

    public function testDecryptString()
    {
        $str = uniqid();
        $encrypted = \Crypt\encrypt($str);

        $this->assertSame(\Crypt\decrypt($encrypted), $str);
    }

    public function testBcryptPasswordAndValidate()
    {
        $password = 'secret';

        $this->assertTrue(is_string($hash = \Crypt\bcrypt($password)));
        $this->assertTrue(password_verify($password, $hash));
    }
}
