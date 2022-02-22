<?php

declare(strict_types=1);

namespace Mikro\Tests;

use PHPUnit\Framework\TestCase;

class ViewTest extends TestCase
{
    public function testInvalidViewPath()
    {
        $this->expectException(\Exception::class);

        \View\render('foo');
    }

    public function testViewRenderer()
    {
        global $mikro;

        $mikro[\View\PATH] = __DIR__;

        file_put_contents($mikro[\View\PATH] . '/view.php', 'content <?= $data ?>');

        $result = \View\render('view', ['data' => 'foo']);

        $this->assertEquals($result, 'content foo');
    }

    public function testViewBlocks()
    {
        global $mikro;

        $this->assertNull(\View\get('invalid region'));
        $this->assertTrue(\View\get('invalid region', true));
        $this->assertTrue(\View\get('invalid region', fn() => true));

        \View\set('name', 'value');
        $this->assertEquals(\View\get('name'), 'value');

        \View\start('region');
            echo 'hello world!';
        \View\stop();

        $this->assertEquals(\View\get('region'), 'hello world!');

        unlink($mikro[\View\PATH] . '/view.php');
    }

    public function testNestedViewBlocks()
    {
        \View\start('content1');
            echo 'content1';
            \View\set('foo', 'bar');
            \View\start('content2');
                echo 'content2';
            \View\stop();
        \View\stop();

        $this->assertEquals(\View\get('content1'), 'content1');
        $this->assertEquals(\View\get('content2'), 'content2');
        $this->assertEquals(\View\get('foo'), 'bar');
    }
}
