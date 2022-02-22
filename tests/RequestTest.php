<?php

declare(strict_types=1);

namespace Mikro\Tests;

use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    public function testRequestMethods()
    {
        $this->assertEquals(\Request\method(), 'GET');
        $this->assertEquals(\Request\path(), '/');
        $this->assertEmpty(\Request\query_string());
        $this->assertEmpty(\Request\query());
        $this->assertEmpty(\Request\all());
        $this->assertNull(\Request\get('foo'));
        $this->assertNull(\Request\input('foo'));
        $this->assertEmpty(\Request\content());
        $this->assertEmpty(\Request\headers());
        $this->assertNull(\Request\header('foo'));
        $this->assertEmpty(\Request\to_array());
    }

    public function testManipulatedRequestMethod()
    {
        $_SERVER['REQUEST_URI'] = '/foo';
        $_SERVER['QUERY_STRING'] = 'foo=bar&baz=qux';
        $_REQUEST['foo'] = 'bar';
        $_REQUEST['baz'] = 'qux';
        $this->assertEquals(\Request\path(), '/foo');
        $this->assertEquals(\Request\query_string(), 'foo=bar&baz=qux');
        $this->assertArrayHasKey('foo', \Request\query());
        $this->assertArrayHasKey('baz', \Request\query());
        $this->assertArrayHasKey('foo', \Request\all());
        $this->assertArrayHasKey('baz', \Request\all());
        $this->assertEquals(\Request\get('foo'), 'bar');
        $this->assertEquals(\Request\input('foo'), 'bar');
        $this->assertEquals(\Request\get('baz'), 'qux');
        $this->assertEquals(\Request\input('baz'), 'qux');
        $this->assertNull(\Request\get('foo-x'));
        $this->assertNull(\Request\input('foo-x'));
        $this->assertEquals(\Request\get('foo-x', 'qux'), 'qux');
        $this->assertEquals(\Request\input('foo-x', 'qux'), 'qux');
    }
}
