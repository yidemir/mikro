<?php

declare(strict_types=1);

namespace Mikro\Tests;

use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    public function testSetConfigItem()
    {
        \Config\set('test', 'value');

        $this->assertSame(\Config\get('test'), 'value');
    }

    public function testSetConfigItemWithDotNotation()
    {
        \Config\set('foo.bar', 'baz');

        $this->assertArrayHasKey('bar', \Config\get('foo'));
        $this->assertSame(\Config\get('foo.bar'), 'baz');
    }

    public function testCollectionDataHasKeys()
    {
        global $mikro;

        $this->assertArrayHasKey('foo', $mikro[\Config\COLLECTION]);
        $this->assertArrayHasKey('test', $mikro[\Config\COLLECTION]);
    }
}
