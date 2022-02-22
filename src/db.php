<?php

declare(strict_types=1);

namespace DB
{
    use function Pagination\paginate;
    use function Request\get as input;

    /**
     * Get PDO connection
     *
     * {@inheritDoc} **Example:**
     * ```php
     * DB\connection(); // Returns PDO instance
     * ```
     *
     * @throws \Exception When the PDO connection is not defined in the global $mikro array
     */
    function connection(): \PDO
    {
        global $mikro;

        if (! isset($mikro[CONNECTION]) || ! ($mikro[CONNECTION] instanceof \PDO)) {
            throw new \Exception('Create PDO instance first');
        }

        return $mikro[CONNECTION];
    }

    /**
     * Creates a table object where you can simply manage SQL Data
     *
     * {@inheritDoc} **Example:**
     * ```php
     * $items = DB\table('items');
     * $items = DB\table('posts', 'post_id');
     *
     * // Retrieve items
     * DB\table('items')->get();
     * DB\table('items')->select('name')->get();
     * DB\table('items')->get('order by id desc');
     * DB\table('items')->find(5);
     * DB\table('items')->find('where id=?', [5]);
     * DB\table('items')->select('count(*)')->column();
     *
     * // Insert item
     * DB\table('items')->insert(['name' => 'foo', 'value' => 'bar']);
     * DB\last_insert_id();
     *
     * // Update item(s)
     * DB\table('items')->update(['name' => 'baz'], $id);
     * DB\table('items')->update(['name' => 'baz'], 'where id=?', [$id]);
     *
     * // Delete item(s)
     * DB\table('items')->delete(5);
     * DB\table('items')->delete('where id=?', [5]);
     *
     * // Paginate item(s)
     * DB\table('items')->paginate();
     * DB\table('items')->paginate('where id=?', [5], [
     *     'page' => Request\get('current_page'),
     *     'per_page' => 25
     * ])
     * DB\table('items')->paginate('', [], ['per_page' => 25]);
     *
     * Pagination\data(); // Gets pagination data
     * ```
     *
     * @throws \Exception When the PDO connection is not defined in the global $mikro array
     */
    function table(string $table, string $primaryKey = 'id'): object
    {
        return new class ($table, $primaryKey) {
            protected string $select = '*';

            public function __construct(
                protected string $table,
                protected string $primaryKey
            ) {
                //
            }

            public function select(string $select): self
            {
                $this->select = $select;

                return $this;
            }

            public function get(string $query = '', array $params = []): array
            {
                $query = "SELECT {$this->select} FROM {$this->table} {$query}";

                return query($query, $params)->fetchAll(\PDO::FETCH_OBJ);
            }

            public function find(int|string $query = '', array $params = []): mixed
            {
                if (\is_numeric($query)) {
                    $params[$this->primaryKey] = (int) $query;
                    $query = "WHERE {$this->primaryKey}=:{$this->primaryKey}";
                }

                $query = "SELECT {$this->select} FROM {$this->table} {$query}";

                return query($query, $params)->fetch(\PDO::FETCH_OBJ);
            }

            public function column(string $query = '', array $params = []): mixed
            {
                $query = "SELECT {$this->select} FROM {$this->table} {$query}";

                return query($query, $params)->fetchColumn();
            }

            public function insert(array $data, string $query = ''): \PDOStatement
            {
                return insert($this->table, $data, $query);
            }

            public function update(
                array $data,
                string $query = '',
                array $params = []
            ): \PDOStatement {
                if (\is_numeric($query)) {
                    $params[$this->primaryKey] = (int) $query;
                    $query = "WHERE {$this->primaryKey}=:{$this->primaryKey}";
                }

                return update($this->table, $data, $query, $params);
            }

            public function delete(int|string $query = '', array $params = []): \PDOStatement
            {
                if (\is_numeric($query)) {
                    $params[$this->primaryKey] = $query;
                    $query = "WHERE {$this->primaryKey}=:{$this->primaryKey}";
                }

                return delete($this->table, $query, $params);
            }

            public function paginate(string $query = '', array $params = [], array $options = []): array
            {
                $total = (int) query(
                    "SELECT COUNT(*) FROM {$this->table} {$query}",
                    $params
                )->fetchColumn();
                $pagination = paginate(
                    $total,
                    (int) ($options['page'] ?? input('page', 1)),
                    (int) ($options['per_page'] ?? 10)
                );

                return query(
                    "SELECT {$this->select} FROM {$this->table} {$query} LIMIT {$pagination['offset']},{$pagination['limit']}",
                    $params
                )->fetchAll(\PDO::FETCH_OBJ);
            }
        };
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
     *
     * @throws \Exception When the PDO connection is not defined in the global $mikro array
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
     *
     * @throws \Exception When the PDO connection is not defined in the global $mikro array
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
     *
     * @throws \Exception When the PDO connection is not defined in the global $mikro array
     */
    function insert(string $table, array $data, string $queryPart = ''): \PDOStatement
    {
        $query = "INSERT INTO {$table} ";
        $query .= querify($data, 'insert');
        $query .= empty($queryPart) ? '' : ' {$queryPart}';

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
     *
     * @throws \Exception When the PDO connection is not defined in the global $mikro array
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
     *
     * @throws \Exception When the PDO connection is not defined in the global $mikro array
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
     *
     * @throws \Exception When the PDO connection is not defined in the global $mikro array
     */
    function last_insert_id(): string|bool
    {
        return connection()->lastInsertId();
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
};
