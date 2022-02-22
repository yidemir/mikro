<?php

declare(strict_types=1);

namespace Mikro\Tests;

use PHPUnit\Framework\TestCase;

class CsrfTest extends TestCase
{
    public function testInvalidRandomValue()
    {
        $this->assertTrue(is_string(\Csrf\generate_random(32)));
    }

    public function testSessionNotActiveException()
    {
        $this->expectException(\Exception::class);

        \Csrf\get();
    }

    public function testGetAndValidateCsrfToken()
    {
        session_start();

        $this->assertTrue(\Csrf\validate(\Csrf\get()));
        $this->assertTrue(is_string(\Csrf\field()));
        $this->assertTrue(strpos(\Csrf\field(), 'input') !== false);
    }
}
