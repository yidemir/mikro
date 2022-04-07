<?php

declare(strict_types=1);

namespace Mikro\Tests;

use PHPUnit\Framework\TestCase;

class HtmlTest extends TestCase
{
    public function testHtmlObject()
    {
        $div = \Html\tag('div');
        $this->assertIsObject($div);
        $this->assertInstanceOf(\Stringable::class, $div);
    }

    public function testHtmlString()
    {
        $div = \Html\tag('div');
        $this->assertSame((string) $div, '<div></div>');

        $div = \Html\tag('div', 'foo');
        $this->assertSame((string) $div, '<div>foo</div>');

        $div = \Html\tag('div', 'foo', ['class' => 'foo']);
        $this->assertSame((string) $div, '<div class="foo">foo</div>');

        $div->id('bar');
        $this->assertSame((string) $div, '<div class="foo" id="bar">foo</div>');

        $div->style(['background-color' => 'red']);
        $this->assertSame((string) $div, '<div class="foo" id="bar" style="background-color:red;">foo</div>');

        $div = \Html\tag('br');
        $this->assertSame((string) $div, '<br>');

        $div = \Html\tag('div', attributes: ['PascalCase' => 'false'])->snakeCase('true')->ABC('def');
        $this->assertSame((string) $div, '<div PascalCase="false" snake-case="true" a-b-c="def"></div>');
    }

    public function testHtmlInvoke()
    {
        $div = \Html\tag('div');
        $this->assertIsString($div());
        $this->assertIsString($div->class('foo')('Content', ['id' => 'foo-5']));
        $this->assertStringContainsString('Content', $div('Content'));
        $this->assertStringContainsString('class="foo"', $div('Content', ['class' => 'foo']));
    }
}
