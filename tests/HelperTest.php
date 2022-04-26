<?php

declare(strict_types=1);

namespace Mikro\Tests;

use PHPUnit\Framework\TestCase;

use function Helper\{arr, str, optional};

class HelperTest extends TestCase
{
    public function testArrHelperInstances()
    {
        $arr = arr();
        $this->assertInstanceOf(\ArrayAccess::class, $arr);
        $this->assertInstanceOf(\Iterator::class, $arr);
        $this->assertInstanceOf(\Countable::class, $arr);
    }

    public function testArrayHelperMethods()
    {
        $empty = fn() => arr([]);

        $filled = fn() => arr([
            'name' => 'value',
            'age' => 10,
            'date' => new \DateTime('now'),
            'items' => [1, 2, 3],
            'info' => [
                'hobbies' => ['books', 'series', 'cooking'],
                'a' => ['b' => 'c']
            ]
        ]);

        $collection = fn() => arr([
            ['name' => 'foo', 'age' => 5, 'items' => ['a', 'b', 355, 72]],
            ['name' => 'bar', 'age' => 12, 'items' => ['x', 'y', 14, 43]],
            ['name' => 'baz', 'age' => 11, 'items' => ['c', 'h', 24, 655]],
            ['name' => 'qux', 'age' => 5, 'items' => ['w', 'g', 116, 75]],
        ]);

        $this->assertIsIterable($empty());
        $this->assertIsIterable($filled());
        $this->assertIsIterable($collection());
        $this->assertIsObject($empty());
        $this->assertIsObject($filled());
        $this->assertIsObject($collection());
        $this->assertEmpty($empty());
        $this->assertNotEmpty($filled());
        $this->assertNotEmpty($collection());
        $this->assertArrayHasKey('name', $filled());
        $this->assertArrayHasKey(0, $collection());
        $this->assertArrayNotHasKey('foo', $filled());
        $this->assertArrayNotHasKey(10, $collection());
        $this->assertArrayNotHasKey('foo', $empty());

        $this->assertIsArray($empty()->all());
        $this->assertEmpty($empty()->all());
        $this->assertIsArray($filled()->all());
        $this->assertNotEmpty($filled()->all());

        $this->assertCount(0, $empty());
        $this->assertCount(5, $filled());
        $this->assertCount(4, $collection());

        $this->assertEmpty($empty()->chunk(1));
        $this->assertEmpty($empty()->chunk(1)->all());
        $this->assertNotEmpty($collection()->chunk(1));
        $this->assertCount(4, $collection()->chunk(1));
        $this->assertCount(2, $collection()->chunk(2));
        $this->assertTrue($filled()->contains('value'));
        $this->assertTrue($filled()->contains('10'));
        $this->assertFalse($filled()->contains('10', true));


        $collected = [];
        $collection()->each(function ($item) use (&$collected) {
            $collected[] = $item['name'];
        });

        $this->assertCount(4, $collected);
        $this->assertArrayNotHasKey('name', $filled()->except(['name']));
        $this->assertCount(2, $collection()->filter(fn($item) => $item['age'] > 10));
        $this->assertCount(1, $filled()->filter(fn($item) => is_string($item)));
        $this->assertTrue($filled()->first() === $filled()['name']);
        $this->assertTrue($collection()->first()['age'] === $collection()[0]['age']);
        $this->assertArrayHasKey('b', arr(['a' => 'b'])->flip());
        $this->assertArrayNotHasKey('name', $filled()->forget('name'));
        $page2 = $collection()->forPage(2, 1);
        $this->assertTrue($page2[0]['name'] === 'bar');
        $this->assertTrue($filled()->get('name') === 'value');
        $this->assertTrue($collection()->get(0)['name'] === 'foo');
        $this->assertIsArray($filled()->get('info.hobbies'));
        $this->assertTrue($filled()->get('info.a.b') === 'c');
        $this->assertCount(2, $collection()->groupBy('age')->get(5));
        $this->assertCount(1, $collection()->groupBy('age')->get(12));
        $this->assertTrue($collection()->has(0));
        $this->assertTrue($filled()->has('name'));
        $this->assertTrue($empty()->isEmpty());
        $this->assertFalse($filled()->isEmpty());
        $this->assertFalse($empty()->isNotEmpty());
        $this->assertTrue($filled()->keys()->first() === 'name');
        $this->assertArrayHasKey('hobbies', $filled()->last());
        $mapped = $collection()->map(function ($item) {
            $item['extra'] = true;

            return $item;
        });
        $keyMapped = $collection()->map(function ($item, $key) {
            $item['key'] = $key;

            return $item;
        });

        $this->assertTrue($mapped->first()['extra']);
        $this->assertTrue($keyMapped->first()['key'] === 0);
        $mappedWithKeys = $collection()->mapWithKeys(function ($value, $key) {
            return [$value['name'] => $value];
        });

        $this->assertIsArray($mappedWithKeys->get('foo'));
        $this->assertTrue($mappedWithKeys->get('foo.age') === 5);
        $this->assertTrue($filled()->merge(['foo' => 'bar'])->get('foo') === 'bar');
        $this->assertTrue($filled()->replace(['name' => 'qux'])->get('name') === 'qux');
        $this->assertArrayHasKey('name', $filled()->only(['name']));
        $this->assertArrayNotHasKey('age', $filled()->only(['name']));
        $this->assertTrue($collection()->pluck('age', 'name')->get('foo') === 5);
        $this->assertTrue($collection()->pop()['name'] === 'qux');
        $this->assertTrue($filled()->pull('name') === 'value');
        $this->assertTrue($filled()->push('text')[0] === 'text');
        $this->assertArrayHasKey('details', $filled()->put('details', 'text'));
        $this->assertTrue($filled()->put('baz', 5)->get('baz') === 5);
        $this->assertTrue($filled()->put('info.name', 'bar')->get('info.name') === 'bar');
        $this->assertTrue($collection()->reverse()->get(1)['age'] === 11);
        $this->assertTrue($filled()->search('value') === 'name');
        $this->assertTrue($collection()->shift()['name'] === 'foo');
        $this->assertTrue(arr([5, 3, 4, 1, 2])->sort()->first() === 1);
        $this->assertIsArray($filled()->toArray());
        $this->assertIsString($filled()->toJson());
        $this->assertIsArray(json_decode($filled()->toJson(), true));

        $this->assertTrue($collection()->transform(function ($item) {
            $item['baz'] = true;

            return $item;
        })->get('0.baz'));

        $filled()->when(true, function ($filled) {
            $this->assertArrayHasKey('name', $filled);
            $this->assertTrue($filled->get('name') === 'value');
        });

        $filled()->unless(false, function ($filled) {
            $this->assertArrayHasKey('name', $filled);
            $this->assertTrue($filled->get('name') === 'value');
        });

        $this->assertTrue($filled()->values()->first() === 'value');

        $data = [];

        foreach ($collection() as $key => $value) {
            $data[] = $value['age'];
        }

        $this->assertCount(4, $data);
        $this->assertSame(arr(['name', 'value'])->implode('|'), 'name|value');
        $this->assertArrayHasKey('name', arr()->parseJson('{"name":"value"}')->all());
        $this->assertCount(5, arr()->parseJson('[1,2,3,4,5]'));
        $this->assertEquals(arr()->parseJson($str = '[1,2,3,4,5]')->toJson(), $str);
    }

    public function testStringHelperMethods()
    {
        $originalStr = 'Lorem ipsum dolor sit amet, consectetur adipisicing elit';
        $str = fn() => str($originalStr);
        $originalPath = '/var/www/public/index.php';
        $path = fn() => str($originalPath);

        $this->assertIsObject($str());
        $this->assertInstanceOf(\Stringable::class, $str());
        $this->assertInstanceOf(\Countable::class, $str());
        $this->assertCount(strlen($originalStr), $str());
        $this->assertStringStartsWith('Lorem', (string) $str());
        $this->assertStringEndsWith('elit', (string) $str());
        $this->assertStringStartsWith('Lorem', $str()->get());
        $this->assertStringEndsWith('elit', $str()->get());

        $this->assertSame((string) $path()->basename(), basename($originalPath));
        $this->assertSame((string) $path()->basename('php'), basename($originalPath, 'php'));
        $this->assertSame((string) $path()->dirname(), dirname($originalPath));
        $this->assertTrue($str()->contains('Lorem'));
        $this->assertFalse($str()->contains('foo'));
        $this->assertTrue($str()->endsWith('elit'));
        $this->assertTrue($str()->contains('Lorem'));
        $this->assertCount(count(explode(' ', $originalStr)), $str()->explode(' '));
        $this->assertCount(count(explode(' ', $originalStr, 2)), $str()->explode(' ', 2));
        $this->assertSame((string) $str()->lcfirst(), lcfirst($originalStr));
        $this->assertStringEndsWith('/', (string) $str()->finish('/'));
        $this->assertSame($str()->length(), strlen($originalStr));
        $this->assertSame((string) $str()->limit(10), substr($originalStr, 0, 10) . '...');
        $this->assertSame((string) $str()->lower(), strtolower($originalStr));
        $this->assertSame((string) $str()->ltrim('Lorem '), ltrim($originalStr, 'Lorem '));
        $this->assertStringStartsWith('Title: ', (string) $str()->prepend('Title: '));
        $this->assertStringNotContainsString('ipsum ', (string) $str()->remove('ipsum '));
        $this->assertStringContainsString('foo', (string) $str()->replace('sit', 'foo'));
        $this->assertSame((string) $str()->rtrim(' elit'), rtrim($originalStr, ' elit'));
        $this->assertSame((string) $str()->reverse(), strrev($originalStr));
        $this->assertStringStartsWith('/', (string) $str()->start('/'));
        $this->assertSame((string) $str()->translate($arr = ['Lorem', 'Lorem!']), strtr($originalStr, $arr));
        $this->assertStringNotContainsString('Lorem', (string) $str()->trim('Lorem'));
        $this->assertStringContainsString('Elit', (string) $str()->title());
        $this->assertSame((string) $str()->ucfirst(), ucfirst($originalStr));
        $this->assertSame((string) $str()->upper(), strtoupper($originalStr));
        $this->assertSame((string) $str()->when(true, fn(object $str) => $str->upper()), strtoupper($originalStr));
        $this->assertSame($str()->wordCount(), str_word_count($originalStr));
    }

    public function testOptionalHelperMethods()
    {
        $array = optional(['name' => 'value']);
        $arrayAccess = optional(arr(['name' => 'value']));
        $stdClass = new \stdClass();
        $stdClass->name = 'value';
        $class = optional($stdClass);

        $this->assertIsObject($array);
        $this->assertIsObject($arrayAccess);
        $this->assertIsObject($class);
        $this->assertInstanceOf(\ArrayAccess::class, $array);
        $this->assertInstanceOf(\ArrayAccess::class, $arrayAccess);
        $this->assertInstanceOf(\ArrayAccess::class, $class);
        $this->assertIsString($array->name);
        $this->assertIsString($array['name']);
        $this->assertNull($array->foo);
        $this->assertNull($array['foo']);
        $this->assertIsString($arrayAccess->name);
        $this->assertIsString($arrayAccess['name']);
        $this->assertNull($arrayAccess->foo);
        $this->assertNull($arrayAccess['foo']);
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

    public function testCurlGetMethod()
    {
        $curl = \Helper\curl('https://jsonplaceholder.typicode.com/posts', 'GET')->exec();

        $this->assertTrue(is_string((string) $curl));
    }

    public function testCurlPostMethod()
    {
        $curl = \Helper\curl('https://jsonplaceholder.typicode.com/posts')
            ->method('post')
            ->asForm()
            ->data([
                'title' => 'foo',
                'body' => 'bar',
                'userId' => 1
            ])
            ->exec();

        $this->assertArrayHasKey('title', (array) $curl->json());
        $this->assertSame($curl->json()['title'] ?? null, 'foo');
    }

    public function testCurlPutMethod()
    {
        $curl = \Helper\curl('https://jsonplaceholder.typicode.com/posts/1')
            ->method('put')
            ->asForm()
            ->data([
                'id' => 1,
                'title' => 'foo',
                'body' => 'bar',
                'userId' => 1
            ])
            ->exec();

        $this->assertArrayHasKey('title', (array) $curl->json());
        $this->assertSame($curl->json()['title'] ?? null, 'foo');
    }

    public function testCurlJsonMethod()
    {
        $response = \Helper\curl('https://jsonplaceholder.typicode.com/posts')->json();

        $this->assertCount(100, (array) $response);
    }

    public function testCurlInfoMethod()
    {
        $curl = \Helper\curl($url = 'https://jsonplaceholder.typicode.com/posts')->exec();

        $this->assertTrue(is_array($curl->getInfo()));
        $this->assertArrayHasKey('url', $curl->getInfo());
        $this->assertSame($url, $curl->getInfo('url'));
        $this->assertSame(200, $curl->getInfo('http_code'));
    }

    public function testCurlRequestIsOk()
    {
        $curl = \Helper\curl('https://jsonplaceholder.typicode.com/posts')->exec();

        $this->assertTrue($curl->isOk());
        $this->assertFalse($curl->isRedirect());
        $this->assertFalse($curl->isFailed());
        $this->assertFalse($curl->isServerError());
        $this->assertFalse($curl->isClientError());
    }

    public function testCurlRequestIsFailed()
    {
        $curl = \Helper\curl('https://jsonplaceholder.typicode.com/posts~')->exec();

        $this->assertTrue($curl->isFailed());
        $this->assertTrue($curl->isClientError());
        $this->assertFalse($curl->isServerError());
        $this->assertFalse($curl->isRedirect());
        $this->assertFalse($curl->isOk());
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
