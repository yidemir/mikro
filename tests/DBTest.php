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
        $insert = \DB\table('items')->fill(['name' => 'bar', 'value' => '100'])->insert();

        $id = \DB\connection()->lastInsertId();

        $this->assertInstanceOf(\PDOStatement::class, $insert);
        $this->assertTrue(is_numeric($id));

        $find = \DB\table('items')->find((int) $id);

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

        $updateWithId = \DB\table('items')
            ->fill(['value' => '101'])
            ->where('id=:id')
            ->bindInt(':id', $id)
            ->update();
        $this->assertInstanceOf(\PDOStatement::class, $updateWithId);
        $find = \DB\table('items')->find((int) $id);
        $this->assertEquals($find->value, '101');

        $updateWithWhereClause = \DB\table('items')
            ->fill(['value' => '102'])
            ->where('id=:id')
            ->bindInt(':id', $id)
            ->update();
        $this->assertInstanceOf(\PDOStatement::class, $updateWithWhereClause);
        $find = \DB\table('items')->find((int) $id);
        $this->assertEquals($find->value, '102');
    }

    public function testDeleteRowFromTable()
    {
        $id = \DB\connection()->lastInsertId();

        $deleteWithId = \DB\table('items')->delete($id);
        $this->assertInstanceOf(\PDOStatement::class, $deleteWithId);
        $find = \DB\table('items')->find((int) $id);
        $this->assertNull($find);

        \DB\table('items')->fill(['name' => 'bar', 'value' => '100'])->insert();
        $id = \DB\connection()->lastInsertId();

        $deleteWithWhereClause = \DB\table('items')->delete('where id=?', [$id]);
        $this->assertInstanceOf(\PDOStatement::class, $deleteWithWhereClause);
        $find = \DB\table('items')->find((int) $id);
        $this->assertNull($find);
    }

    public function testColumnMethodFromTable()
    {
        foreach (range(1, 10) as $i) {
            \DB\table('items')->fill(['name' => 'foo', 'value' => $i])->insert();
        }

        $count = \DB\table('items')->select('count(*)')->column();

        $this->assertEquals($count, count(range(1, 10)));
    }

    public function testPaginateMethodFromTable()
    {
        $paginatedItems = \DB\table('items')->paginate();
        $this->assertEquals(count($paginatedItems), 10);
        $this->assertInstanceOf(\Iterator::class, $paginatedItems);
        $this->assertInstanceOf(\ArrayAccess::class, $paginatedItems);
        $this->assertInstanceOf(\Countable::class, $paginatedItems);
        $this->assertFalse($paginatedItems->isEmpty());
        $this->assertIsArray($paginatedItems->getPagination()->getPageNumbers());
        $this->assertIsNotArray($paginatedItems);
        $this->assertIsArray($paginatedItems->toArray());

        $paginatedItems = \DB\table('items')->paginate(perPage: 3);
        $this->assertEquals(count($paginatedItems), 3);
    }

    public function testQueryBuilderMethods()
    {
        $this->assertIsObject(\DB\builder());
        $this->assertInstanceOf(\Stringable::class, \DB\builder());
        $this->assertEquals(\DB\builder()->select('*')->from('items')->toSql(), 'SELECT * FROM items');
        $this->assertEquals(\DB\builder()->table('items', '*')->toSql(), $sql = 'SELECT * FROM items');
        $table = fn() => \DB\builder()->table('items', '*');
        $this->assertEquals(
            $table()->join('posts ON items.post_id = posts.id')->toSql(),
            $sql . ' JOIN posts ON items.post_id = posts.id'
        );
        $this->assertEquals(
            $table()->join('foo')->join('bar')->toSql(),
            $sql . ' JOIN foo bar'
        );
        $this->assertEquals(
            $table()
                ->join('default')
                ->innerJoin('inner')
                ->crossJoin('cross')
                ->leftJoin('left')
                ->rightJoin('right')
                ->outerJoin('outer')
                ->toSql(),
            $sql . ' JOIN default INNER JOIN inner CROSS JOIN cross LEFT JOIN left RIGHT JOIN right OUTER JOIN outer'
        );
        $this->assertEquals(
            $table()->innerJoin('inner1')->innerJoin('inner2')->leftJoin('left')->toSql(),
            $sql . ' INNER JOIN inner1inner2 LEFT JOIN left'
        );
        $this->assertEquals(
            $table()->where('id=:id')->toSql(),
            $sql . ' WHERE id=:id'
        );
        $this->assertEquals(
            $table()->where('id=:id')->join('foo ON bar=baz')->toSql(),
            $sql . ' JOIN foo ON bar=baz WHERE id=:id'
        );
        $this->assertEquals(
            $table()->groupBy('field')->toSql(),
            $sql . ' GROUP BY field'
        );
        $this->assertEquals(
            $table()->where('where')->innerJoin('inner')->groupBy('field'),
            $sql . ' INNER JOIN inner WHERE where GROUP BY field'
        );
        $this->assertEquals(
            $table()->having('having')->toSql(),
            $sql . ' HAVING having'
        );
        $this->assertEquals(
            $table()->orderBy('field DESC')->toSql(),
            $sql . ' ORDER BY field DESC'
        );
        $this->assertEquals(
            $table()->limit('100')->toSql(),
            $sql . ' LIMIT 100'
        );
        $this->assertEquals(
            $table()
                ->orderBy('field')
                ->having('having')
                ->where('id=5')
                ->limit('5')
                ->select('id, name')
                ->from('table')
                ->leftJoin('join')
                ->groupBy('f')
                ->toSql(),
            'SELECT id, name FROM table LEFT JOIN join WHERE id=5 GROUP BY f HAVING having ORDER BY field LIMIT 5'
        );

        $insert = fn() => \DB\builder();
        $this->assertEquals($insert()->insertInto('table'), 'INSERT INTO table');
        $this->assertEquals(
            $insert()->insertInto('table (id, name)')->values('(:id, :name)')->toSql(),
            'INSERT INTO table (id, name) VALUES (:id, :name)'
        );
        $this->assertEquals(
            $insert()->insertInto('table (id, name)')->valuesArray([':id', ':name'])->toSql(),
            'INSERT INTO table (id, name) VALUES (:id, :name)'
        );
        $this->assertEquals(
            $insert()->insertInto('table (name)')->values('(:name)')->onDuplicateKeyUpdate('update')->toSql(),
            'INSERT INTO table (name) VALUES (:name) ON DUPLICATE KEY UPDATE update'
        );
        $this->assertEquals(
            $insert()->onDuplicateKeyUpdate('update')->values('(:name)')->insertInto('table (name)')->toSql(),
            'INSERT INTO table (name) VALUES (:name) ON DUPLICATE KEY UPDATE update'
        );
        $this->assertEquals(
            $insert()->insertInto('table')->set('name=:name, key=:key')->toSql(),
            'INSERT INTO table SET name=:name, key=:key'
        );
        $this->assertEquals(
            $insert()->insertInto('table')->setArray(['name' => ':name', 'key' => ':key'])->toSql(),
            'INSERT INTO table SET name=:name, key=:key'
        );
        $this->assertEquals(
            $insert()->onDuplicateKeyUpdate('update')->insertInto('table')->setArray(['name' => ':name', 'key' => ':key'])->toSql(),
            'INSERT INTO table SET name=:name, key=:key ON DUPLICATE KEY UPDATE update'
        );

        $update = fn() => \DB\builder()->update('table');
        $this->assertEquals($update()->toSql(), 'UPDATE table');
        $this->assertEquals(
            $update()->set('name=:name')->toSql(),
            'UPDATE table SET name=:name'
        );
        $this->assertEquals(
            $update()->setArray(['name' => ':name'])->toSql(),
            'UPDATE table SET name=:name'
        );
        $this->assertEquals(
            $update()->where('id=:id')->set('name=:name')->toSql(),
            'UPDATE table SET name=:name WHERE id=:id'
        );
        $this->assertEquals(
            $update()->limit(':limit')->set('name=:name')->toSql(),
            'UPDATE table SET name=:name LIMIT :limit'
        );
        $this->assertEquals(
            $update()->orderBy('field')->set('name=:name')->toSql(),
            'UPDATE table SET name=:name ORDER BY field'
        );
        $this->assertEquals(
            $update()->orderBy('order')->set('set')->where('where')->limit('limit')->set('set')->toSql(),
            'UPDATE table SET set set WHERE where ORDER BY order LIMIT limit'
        );

        $delete = fn() => \DB\builder()->deleteFrom('table');
        $this->assertEquals($delete(), 'DELETE FROM table');
        $this->assertEquals($delete()->where('where')->toSql(), 'DELETE FROM table WHERE where');
        $this->assertEquals($delete()->orderBy('order')->toSql(), 'DELETE FROM table ORDER BY order');
        $this->assertEquals($delete()->limit('limit')->toSql(), 'DELETE FROM table LIMIT limit');
        $this->assertEquals(
            $delete()->orderBy('id DESC')->where('id=:id')->limit(':limit')->toSql(),
            'DELETE FROM table WHERE id=:id ORDER BY id DESC LIMIT :limit'
        );

        $binds = [
            ['param' => ':status', 'var' => true, 'type' => \PDO::PARAM_BOOL],
            ['param' => ':shipped_at', 'var' => null, 'type' => \PDO::PARAM_NULL],
            ['param' => ':age', 'var' => 10, 'type' => \PDO::PARAM_INT],
            ['param' => ':name', 'var' => 'Ali', 'type' => \PDO::PARAM_STR],
            ['param' => ':shipped_at', 'var' => null, 'type' => \PDO::PARAM_NULL],
        ];

        $params = \DB\builder()->bind(':name', 'name')->getParameters();
        $this->assertTrue(isset($params[':name']));
        $this->assertCount(1, $params);
        $this->assertTrue($params[':name']['param'] === ':name');
        $this->assertTrue($params[':name']['var'] === 'name');
        $this->assertTrue($params[':name']['type'] === \PDO::PARAM_STR);

        $builder = \DB\builder()->binds($binds);
        $this->assertArrayHasKey(':status', $builder->getParameters());
        $this->assertArrayHasKey(':shipped_at', $builder->getParameters());

        $builder = \DB\builder()->table('items', '*');
        $builder->bindSequence([5, 6], \PDO::PARAM_INT)->bind([7, 8])->bind([9, 10]);
        $this->assertCount(6, $builder->getParameters());
        $this->assertArrayHasKey(1, $builder->getParameters());
        $this->assertSame($builder->getParameters()[1]['var'], 5);
        $this->assertSame($builder->getParameters()[1]['param'], 1);
        $this->assertSame($builder->getParameters()[1]['type'], \PDO::PARAM_INT);
        $this->assertArrayHasKey(3, $builder->getParameters());
        $this->assertSame($builder->getParameters()[3]['var'], 7);
        $this->assertSame($builder->getParameters()[3]['param'], 3);
        $this->assertSame($builder->getParameters()[3]['type'], \PDO::PARAM_STR);

        $builder = \DB\builder()
            ->table('items', '*')
            ->where('id=?', [5])
            ->limit('?', [100])
            ->where('AND status=?', ['active']);

        $builder->build();

        $this->assertArrayHasKey(1, $builder->getParameters());
        $this->assertSame($builder->getParameters()[1]['var'], 5);
        $this->assertSame($builder->getParameters()[1]['param'], 1);
        $this->assertSame($builder->getParameters()[1]['type'], \PDO::PARAM_STR);

        $this->assertArrayHasKey(2, $builder->getParameters());
        $this->assertSame($builder->getParameters()[2]['var'], 'active');
        $this->assertSame($builder->getParameters()[2]['param'], 2);
        $this->assertSame($builder->getParameters()[2]['type'], \PDO::PARAM_STR);

        $this->assertArrayHasKey(3, $builder->getParameters());
        $this->assertSame($builder->getParameters()[3]['var'], 100);
        $this->assertSame($builder->getParameters()[3]['param'], 3);
        $this->assertSame($builder->getParameters()[3]['type'], \PDO::PARAM_STR);

        $builder = \DB\builder()
            ->insertInto('items (name, quantity)')
            ->valuesArray(['?', '?'], ['name', 'quantity'])
            ->build();

        $this->assertSame($builder['sql'], 'INSERT INTO items (name, quantity) VALUES (?, ?)');

        $this->assertArrayHasKey(1, $builder['parameters']);
        $this->assertSame($builder['parameters'][1]['var'], 'name');
        $this->assertSame($builder['parameters'][1]['param'], 1);
        $this->assertSame($builder['parameters'][1]['type'], \PDO::PARAM_STR);

        $this->assertArrayHasKey(2, $builder['parameters']);
        $this->assertSame($builder['parameters'][2]['var'], 'quantity');
        $this->assertSame($builder['parameters'][2]['param'], 2);
        $this->assertSame($builder['parameters'][2]['type'], \PDO::PARAM_STR);

        $builder = \DB\builder()
            ->update('items')
            ->where('id=?', [1])
            ->setArray(['name' => '?'], ['new name'])
            ->where('AND status=?', ['active'])
            ->build();

        $this->assertSame($builder['sql'], 'UPDATE items SET name=? WHERE id=? AND status=?');

        $this->assertArrayHasKey(1, $builder['parameters']);
        $this->assertSame($builder['parameters'][1]['var'], 'new name');
        $this->assertSame($builder['parameters'][1]['param'], 1);
        $this->assertSame($builder['parameters'][1]['type'], \PDO::PARAM_STR);

        $this->assertArrayHasKey(2, $builder['parameters']);
        $this->assertSame($builder['parameters'][2]['var'], 1);
        $this->assertSame($builder['parameters'][2]['param'], 2);
        $this->assertSame($builder['parameters'][2]['type'], \PDO::PARAM_STR);

        $this->assertArrayHasKey(3, $builder['parameters']);
        $this->assertSame($builder['parameters'][3]['var'], 'active');
        $this->assertSame($builder['parameters'][3]['param'], 3);
        $this->assertSame($builder['parameters'][3]['type'], \PDO::PARAM_STR);

        $builder = \DB\builder()
            ->deleteFrom('items')
            ->limit('?', [100])
            ->where('id=?', [3])
            ->build();

        $this->assertSame($builder['sql'], 'DELETE FROM items WHERE id=? LIMIT ?');

        $this->assertArrayHasKey(1, $builder['parameters']);
        $this->assertSame($builder['parameters'][1]['var'], 3);
        $this->assertSame($builder['parameters'][1]['param'], 1);
        $this->assertSame($builder['parameters'][1]['type'], \PDO::PARAM_STR);

        $this->assertArrayHasKey(2, $builder['parameters']);
        $this->assertSame($builder['parameters'][2]['var'], 100);
        $this->assertSame($builder['parameters'][2]['param'], 2);
        $this->assertSame($builder['parameters'][2]['type'], \PDO::PARAM_STR);
    }
}
