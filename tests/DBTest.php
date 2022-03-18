<?php

declare(strict_types=1);

namespace Mikro\Tests;

use PHPUnit\Framework\TestCase;

class DBTest extends TestCase
{
    public function __construct()
    {
        global $mikro;

        $mikro[\DB\CONNECTION] = new \PDO('sqlite::memory:');
        $mikro[\DB\CONNECTION]->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        parent::__construct();
    }

    public function testConnection()
    {
        $this->assertInstanceOf(\PDO::class, \DB\connection());
    }

    public function testDatabaseExecMethod()
    {
        $execute = \DB\exec('CREATE TABLE items (id INTEGER PRIMARY KEY, name VARCHAR, value INTEGER)');

        $this->assertTrue(is_int($execute));
    }

    public function testQueryMethodAndGetEmptyResult()
    {
        $this->assertEmpty(\DB\query('select * from items')->fetchAll());
    }

    public function testQuerifyMethodForInsertAndUpdate()
    {
        $array = ['name' => 'foo', 'value' => 20];
        $insert = \DB\querify($array, 'insert');
        $update = \DB\querify($array, 'update');

        $this->assertSame($insert, '(name,value) VALUES (?,?)');
        $this->assertSame($update, 'name=?,value=?');
    }

    public function testInsertMethod()
    {
        $insert = \DB\insert('items', ['name' => 'foo', 'value' => 10]);

        $this->assertInstanceOf(\PDOStatement::class, $insert);
        $this->assertTrue(is_numeric(\DB\last_insert_id()));

        $insertedData = \DB\query('select * from items')->fetch();

        $this->assertEquals($insertedData['value'], 10);
    }

    public function testUpdateMethod()
    {
        $update = \DB\update('items', ['value' => 20], 'where name=?', ['foo']);

        $this->assertInstanceOf(\PDOStatement::class, $update);

        $updatedData = \DB\query('select * from items')->fetch();

        $this->assertEquals($updatedData['value'], 20);
    }

    public function testDeleteMethod()
    {
        $delete = \DB\delete('items', 'where name=?', ['foo']);

        $this->assertInstanceOf(\PDOStatement::class, $delete);

        $deletedData = \DB\query('select * from items')->fetch();

        $this->assertFalse($deletedData);
    }

    public function testInsertRowToTable()
    {
        $insert = \DB\table('items')->insert(['name' => 'bar', 'value' => '100']);

        $id = \DB\connection()->lastInsertId();

        $this->assertInstanceOf(\PDOStatement::class, $insert);
        $this->assertTrue(is_numeric($id));

        $find = \DB\table('items')->find($id);

        $this->assertIsIterable($find);
        $this->assertIsArray($find->toArray());
        $this->assertArrayHasKey('id', $find->toArray());
        $this->assertArrayHasKey('name', $find->toArray());
        $this->assertArrayHasKey('value', $find->toArray());
        $this->assertEquals($find['id'], $id);
        $this->assertEquals($find['name'], 'bar');
        $this->assertEquals($find['value'], '100');
    }

    public function testUpdateRowFromTable()
    {
        $id = \DB\connection()->lastInsertId();

        $updateWithId = \DB\table('items')->update(['value' => '101'], $id);
        $this->assertInstanceOf(\PDOStatement::class, $updateWithId);
        $find = \DB\table('items')->find($id);
        $this->assertEquals($find->value, '101');

        $updateWithWhereClause = \DB\table('items')->update(['value' => '102'], 'where id=?', [$id]);
        $this->assertInstanceOf(\PDOStatement::class, $updateWithWhereClause);
        $find = \DB\table('items')->find($id);
        $this->assertEquals($find->value, '102');
    }

    public function testDeleteRowFromTable()
    {
        $id = \DB\connection()->lastInsertId();

        $deleteWithId = \DB\table('items')->delete($id);
        $this->assertInstanceOf(\PDOStatement::class, $deleteWithId);
        $find = \DB\table('items')->find($id);
        $this->assertNull($find);

        \DB\table('items')->insert(['name' => 'bar', 'value' => '100']);
        $id = \DB\connection()->lastInsertId();

        $deleteWithWhereClause = \DB\table('items')->delete('where id=?', [$id]);
        $this->assertInstanceOf(\PDOStatement::class, $deleteWithWhereClause);
        $find = \DB\table('items')->find($id);
        $this->assertNull($find);
    }

    public function testColumnMethodFromTable()
    {
        foreach (range(1, 10) as $i) {
            \DB\table('items')->insert(['name' => 'foo', 'value' => $i]);
        }

        $count = \DB\table('items')->select('count(*)')->column();

        $this->assertEquals($count, count(range(1, 10)));
    }

    public function testCollection()
    {
        $collection = \DB\collection($array = range(1, 10));

        $this->assertArrayHasKey(0, $collection);
        $this->assertArrayHasKey(9, $collection);
        $this->assertInstanceOf(\Iterator::class, $collection);
        $this->assertInstanceOf(\ArrayAccess::class, $collection);
        $this->assertInstanceOf(\Countable::class, $collection);
        $this->assertIsArray($collection->toArray());
        $this->assertTrue($collection->valid());
        $this->assertEquals($collection->current(), 1);
        $collection->next();
        $this->assertTrue($collection->valid());
        $this->assertEquals($collection->current(), 2);
        $this->assertEquals($collection->count(), count($array));
        $collection[] = 11;
        $this->assertCount(11, $collection);
        $this->assertArrayHasKey(10, $collection);
        $mapped = $collection->map(fn($item) => $item === 1 ? null : $item);
        $instance = $collection->each(fn($item) => $item === 1 ? null : $item);
        $this->assertTrue($mapped[0] === null);
        unset($mapped[0]);
        $this->assertCount(10, $mapped);
        $this->assertNotCount(10, $instance);
        $this->assertTrue($instance[0] === null);
        $paginated = $collection->paginate($perPage = 2, $currentPage = 1);
        $this->assertCount($perPage, $collection);
        $this->assertEquals($collection[0], null);
        $this->assertEquals($collection[1], 2);
        $this->assertCount(6, $collection->pages());
        $this->assertIsString($collection->links());
        $this->assertFalse($collection->isEmpty());
        $this->assertNull($collection->first());
        $this->assertTrue(2 === $collection->last());
        $this->assertNull($collection->find(0));
        $this->assertNull($collection->find(100));
    }

    public function testPaginateMethodFromTable()
    {
        $paginatedItems = \DB\table('items')->paginate();
        $this->assertEquals(count($paginatedItems), 10);
        $this->assertInstanceOf(\Iterator::class, $paginatedItems);
        $this->assertInstanceOf(\ArrayAccess::class, $paginatedItems);
        $this->assertInstanceOf(\Countable::class, $paginatedItems);
        $this->assertFalse($paginatedItems->isEmpty());
        $this->assertIsString($paginatedItems->getPagination()->getLinks());
        $this->assertIsNotArray($paginatedItems);
        $this->assertIsArray($paginatedItems->toArray());

        $paginatedItems = \DB\table('items')->paginate('', [], ['per_page' => 3]);
        $this->assertEquals(count($paginatedItems), 3);
    }
}
