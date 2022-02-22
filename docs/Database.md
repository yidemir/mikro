# Database

Setup:
```php
$mikro[DB\CONNECTION] = new PDO('connection-string');
```

## Query
```php
$sth = DB\query('select * from items');
$items = $sth->fetchAll();

$sth = DB\query('select * from items where id=:id', ['id' => 5]);
$item = $sth->fetch();

DB\query('insert into items (name, value) values (?, ?)', [$name, $value]);
```

## Execute
```php
DB\exec('create table if not exists items ...');
```

## Insert
```php
DB\insert('items', ['name' => 'value']);
```

## Update
```php
DB\update('items', ['name' => 'foo', 'value' => 'bar']);
DB\update('items', ['name' => 'foo', 'value' => 'bar'], 'where id=?', [$id]);
```

## Delete
```php
DB\delete('items');
DB\delete('items', 'where id=?', [$id]);
```

## Last Insert ID
```php
DB\insert('items', ['name' => 'value']);
DB\last_insert_id();
```

## Table Wrapper
```php
$instance = DB\table('table-name'); // class
$instance = DB\table('table-name', 'primary-key');
```

### Retrieve Items
```php
DB\table('items')->get(); // get all items
DB\table('items')->select('name')->get(); // get all items with select statement
DB\table('items')->get('order by id desc'); // get items with order statement
DB\table('items')->find(5); // get item id 5
DB\table('items')->find('where id=?', [5]); // get item with where statement
DB\table('items')->select('count(*)')->column(); // get items count with column method
```

### Insert Item
```php
DB\table('items')->insert(['name' => 'foo', 'value' => 'bar']);
DB\last_insert_id();
```

### Update Item(s)
```php
DB\table('items')->update(['name' => 'baz'], $id);
// if second parameter is numeric, trigger where statement  with primary key

DB\table('items')->update(['name' => 'baz'], 'where id=?', [$id]);
```

### Delete Item(s)
```php
DB\table('items')->delete(5); // if first parameter is numeric, trigger where statement with primary key
DB\table('items')->delete('where id=?', [5]);
```

### Paginate Item(s)
```php
DB\table('items')->paginate();
DB\table('items')->paginate('where id=?', [5], [
    'page' => Request\get('current_page'),
    'per_page' => 25
])
DB\table('items')->paginate('', [], ['per_page' => 25]);

Pagination\data(); // Gets pagination data (array)
```
