<?php

declare(strict_types=1);

namespace Mikro\Tests;

use PHPUnit\Framework\TestCase;

use function Helper\optional;

class HelperTest extends TestCase
{
    public function testOptionalHelperMethods()
    {
        $array = optional(['name' => 'value']);
        $stdClass = new \stdClass();
        $stdClass->name = 'value';
        $class = optional($stdClass);

        $this->assertIsObject($array);
        $this->assertIsObject($class);
        $this->assertInstanceOf(\ArrayAccess::class, $array);
        $this->assertInstanceOf(\ArrayAccess::class, $class);
        $this->assertIsString($array->name);
        $this->assertIsString($array['name']);
        $this->assertNull($array->foo);
        $this->assertNull($array['foo']);
        $this->assertIsString($class->name);
        $this->assertIsString($class['name']);
        $this->assertNull($class->foo);
        $this->assertNull($class['foo']);
    }

    public function testCsrfInvalidRandomValue()
    {
        $this->assertTrue(is_string(\Helper\csrf()->generateRandom(32)));
    }

    public function testSessionNotActiveException()
    {
        $this->expectException(\Exception::class);

        \Helper\csrf()->get();
    }

    public function testGetAndValidateCsrfToken()
    {
        session_start();

        $this->assertTrue(\Helper\csrf()->validate(\Helper\csrf()->get()));
        $this->assertTrue(is_string(\Helper\csrf()->field()));
        $this->assertTrue(strpos(\Helper\csrf()->field(), 'input') !== false);

        session_destroy();
    }

    public function testHtmlObject()
    {
        $div = \Helper\html('div');
        $this->assertIsObject($div);
        $this->assertInstanceOf(\Stringable::class, $div);
    }

    public function testHtmlString()
    {
        $div = \Helper\html('div');
        $this->assertSame((string) $div, '<div></div>');

        $div = \Helper\html('div', 'foo');
        $this->assertSame((string) $div, '<div>foo</div>');

        $div = \Helper\html('div', 'foo', ['class' => 'foo']);
        $this->assertSame((string) $div, '<div class="foo">foo</div>');

        $div->id('bar');
        $this->assertSame((string) $div, '<div class="foo" id="bar">foo</div>');

        $div->style(['background-color' => 'red']);
        $this->assertSame((string) $div, '<div class="foo" id="bar" style="background-color:red;">foo</div>');

        $div = \Helper\html('br');
        $this->assertSame((string) $div, '<br>');

        $div = \Helper\html('div', attributes: ['PascalCase' => 'false'])->snakeCase('true')->ABC('def');
        $this->assertSame((string) $div, '<div PascalCase="false" snake-case="true" a-b-c="def"></div>');
    }

    public function testHelperPaginateMethod()
    {
        $paginate = \Helper\paginate(100);
        $data = $paginate->getData();

        $this->assertArrayHasKey('offset', $data);
        $this->assertArrayHasKey('limit', $data);
        $this->assertArrayHasKey('current_page', $data);
        $this->assertArrayHasKey('next_page', $data);
        $this->assertArrayHasKey('previous_page', $data);
        $this->assertArrayHasKey('total_page', $data);

        $this->assertSame($data['offset'], 0);
        $this->assertSame($data['limit'], 10);
        $this->assertSame($data['current_page'], 1);
        $this->assertSame($data['next_page'], 2);
        $this->assertSame($data['previous_page'], 1);
        $this->assertSame($data['total_page'], 10);

        $this->assertSame($paginate->getOffset(), 0);
        $this->assertSame($paginate->getLimit(), 10);
        $this->assertSame($paginate->getCurrentPage(), 1);
        $this->assertSame($paginate->getNextPage(), 2);
        $this->assertSame($paginate->getPreviousPage(), 1);
        $this->assertSame($paginate->getTotalPage(), 10);

        $paginate = \Helper\paginate(100, 7, 10);

        $this->assertSame($paginate->getOffset(), 60);
        $this->assertSame($paginate->getLimit(), 10);
        $this->assertSame($paginate->getCurrentPage(), 7);
        $this->assertSame($paginate->getNextPage(), 8);
        $this->assertSame($paginate->getPreviousPage(), 6);
        $this->assertSame($paginate->getTotalPage(), 10);
    }

    public function testHelperFlashMethod()
    {
        session_start();
        \Helper\flash()->add('error1');
        \Helper\flash()->add('error2');
        $this->assertTrue(in_array('error1', \Helper\flash()->get()));
        \Helper\flash('error3');
        $this->assertTrue(in_array('error3', \Helper\flash()->get()));
        \Helper\flash('default1');
        \Helper\flash('default2');
        \Helper\flash()->add('warning1', 'warning');
        \Helper\flash()->add('warning2', 'warning');
        $default = \Helper\flash()->get();
        $warning = \Helper\flash()->get('warning');
        $this->assertTrue(in_array('default1', $default));
        $this->assertTrue(in_array('default2', $default));
        $this->assertTrue(in_array('warning1', $warning));
        $this->assertTrue(in_array('warning2', $warning));
        $this->assertArrayNotHasKey(0, \Helper\flash()->get());
        $this->assertArrayNotHasKey(0, \Helper\flash()->get('warning'));
        session_destroy();
    }
}
