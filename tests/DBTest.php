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
}
