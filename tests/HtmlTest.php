<?php

declare(strict_types=1);

namespace Mikro\Tests;

use PHPUnit\Framework\TestCase;

class HtmlTest extends TestCase
{
    public function testHtmlObject()
    {
        $div = \Html\tag('div');
        $this->assertTrue(is_object($div));
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
}
