<?php

declare(strict_types=1);

namespace DB
{
    use Mikro\Exceptions\MikroException;

    /**
     * Get PDO connection
     *
     * {@inheritDoc} **Example:**
     * ```php
     * DB\connection(); // Returns PDO instance
     * ```
     *
     * @throws MikroException When the PDO connection is not defined in the global $mikro array
     */
    function connection(): \PDO
    {
        global $mikro;

        if (! isset($mikro[CONNECTION]) || ! ($mikro[CONNECTION] instanceof \PDO)) {
            throw new MikroException('Create PDO instance first');
        }

        return $mikro[CONNECTION];
    }

    /**
     * Executes an SQL query with parameters
     *
     * {@inheritDoc} **Example:**
     * ```php
     * DB\query('select * from items')->fetchAll();
     * DB\query('select * from items where id=?', [$id])->fetch();
     * DB\query('insert into items (name, value) values (?, ?)', [$name, $value]);
     * ```
     */
    function query(string $query, array $params = []): \PDOStatement
    {
        $sth = connection()->prepare($query);
        $sth->execute($params);

        return $sth;
    }

    /**
     * Executes an SQL query
     *
     * {@inheritDoc} **Example:**
     * ```php
     * DB\exec('create table if not exists items ...');
     * ```
     */
    function exec(string $query): int|bool
    {
        return connection()->exec($query);
    }

    /**
     * Inserts data to the specified table
     *
     * {@inheritDoc} **Example:**
     * ```php
     * DB\insert('items', ['name' => 'foo', 'value' => 'bar']);
     * ```
     */
    function insert(string $table, array $data, string $queryPart = ''): \PDOStatement
    {
        $query = "INSERT INTO {$table} ";
        $query .= querify($data, 'insert');
        $query .= empty($queryPart) ? '' : " {$queryPart}";

        return query($query, \array_values($data));
    }

    /**
     * Updates the data(s) in the specified table
     *
     * {@inheritDoc} **Example:**
     * ```php
     * DB\update('items', ['name' => 'foo', 'value' => 'bar']);
     * DB\update('items', ['name' => 'foo', 'value' => 'bar'], 'where id=?', [$id]);
     * ```
     */
    function update(
        string $table,
        array $data,
        string $query = '',
        array $parameters = []
    ): \PDOStatement {
        $query = "UPDATE {$table} SET " . querify($data, 'update') . " {$query}";
        $params = \array_values($data);

        if (! empty($parameters)) {
            $params = \array_merge($params, $parameters);
        }

        return query($query, $params);
    }

    /**
     * Deletes the data(s) from the specified table
     *
     * {@inheritDoc} **Example:**
     * ```php
     * DB\delete('items');
     * DB\delete('items', 'where id=?', [$id]);
     * ```
     */
    function delete(
        string $table,
        string $query = '',
        array $parameters = []
    ): \PDOStatement {
        $query = "DELETE FROM {$table} {$query}";

        return query($query, $parameters);
    }

    /**
     * Get last insert id after insert query
     *
     * {@inheritDoc} **Example:**
     * ```php
     * DB\last_insert_id();
     * ```
     */
    function last_insert_id(): int|string|bool
    {
        $id = connection()->lastInsertId();

        if (\is_numeric($id)) {
            return (int) $id;
        }

        return $id;
    }

    /**
     * @internal
     */
    function querify(array $data, string $type): string
    {
        $string = '';

        switch ($type) {
            case 'insert':
                $arrayParameters = \array_values($data);
                $columnsString = \implode(',', \array_keys($data));
                $valuesString = \implode(',', \array_fill(0, \count($arrayParameters), '?'));
                $string = "({$columnsString}) VALUES ({$valuesString})";
                break;
            case 'update':
                foreach ($data as $key => $value) {
                    $string .= "{$key}=?,";
                }

                $string = \rtrim($string, ',');
                break;
        }

        return $string;
    }

    /**
     * DB connection constant
     *
     * {@inheritDoc} **Example:**
     * ```php
     * $mikro[DB\CONNECTION] = new PDO('...');
     * ```
     */
    const CONNECTION = 'DB\CONNECTION';
}
