<?php

declare(strict_types=1);

namespace Mikro\Tests;

use PHPUnit\Framework\TestCase;

class CacheTest extends TestCase
{
    public function setUp(): void
    {
        global $mikro;

        $mikro[\Cache\PATH] = __DIR__;
    }

    public function testCachePathIsDefined()
    {
        $this->assertIsString(\Cache\path());
    }

    public function testCachePathIsTrue()
    {
        $this->assertSame(\Cache\path(), __DIR__);
    }

    public function testCachePathIsAvailable()
    {
        $this->assertTrue(is_readable(\Cache\path()) && is_writable(\Cache\path()));
    }

    public function testUndefinedCache()
    {
        $this->assertNull(\Cache\get('test'));
    }

    public function testSetCache()
    {
        \Cache\set('test', 'value');

        $this->assertTrue(\Cache\has('test'));
    }

    public function testGetAndValidatePresentCache()
    {
        $this->assertSame(\Cache\get('test'), 'value');
    }

    public function testRemoveCache()
    {
        \Cache\remove('test');

        $this->assertNull(\Cache\get('test'));
    }

    public function testFlushMethod()
    {
        foreach (range(1, 5) as $i) {
            \Cache\set((string) $i, $i);
        }

        \Cache\flush();

        foreach (range(1, 5) as $i) {
            $this->assertFalse(\Cache\has((string) $i));
        }
    }

    public function testMemcachedDriver()
    {
        global $mikro;

        $this->assertFalse(\Cache\memcached());

        $mikro[\Cache\DRIVER] = 'memcached';

        if (extension_loaded('memcached')) {
            $memcached = new \Memcached();
            $memcached->addServer('localhost', 11211);

            $this->assertInstanceOf(\Memcached::class, $memcached);
            \Container\set(\Memcached::class, $memcached);
            $this->assertInstanceOf(\Memcached::class, \Container\get(\Memcached::class));

            \Cache\set('name', 'value');
            $this->assertEquals('value', \Cache\get('name'));
            $this->assertEquals('value', \Cache\memcached()->get('name'));
            $this->assertNull(\Cache\get('foobarbaz'));
        }

        unset($mikro[\Cache\DRIVER]);
    }

    public function testRememberMethod()
    {
        $data = \Cache\remember('data', function () {
            return 100;
        });

        $this->assertTrue($data === \Cache\get('data'));
        $this->assertTrue(100 === \Cache\get('data'));
        $this->assertTrue($data === 100);

        \Cache\remove('data');
    }
}
