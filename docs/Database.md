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
