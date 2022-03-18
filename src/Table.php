<?php

declare(strict_types=1);

namespace DB
{
    use Helper;
    use Pagination;
    use Mikro\Exceptions\{MikroException, DataNotFoundException};

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
     * DB\table('items')->orderBy('id DESC')->get();
     * DB\table('items')->find(5);
     * DB\table('items')->where('id=:id')->bind(':id', 5)->find();
     * DB\table('items')->select('count(*)')->column();
     * DB\table('items')->count();
     *
     * // Insert item
     * DB\table('items')->fill(['name' => 'foo', 'value' => 'bar'])->insert();
     * DB\last_insert_id();
     *
     * // Update item(s)
     * DB\table('items')
     *     ->fill(['name' => 'baz'])
     *     ->where('id=:id')
     *     ->bind(':id', $id)
     *     ->update();
     *
     * // Delete item(s)
     * DB\table('items')->where('id=:id')->bind(':id', $id)->delete();
     *
     * // Paginate item(s)
     * DB\table('items')->paginate();
     * DB\table('items')->where('type=:type')->bind(':type', 'footype')
     *     ->paginate($currentPage = 1, $perPage = 10)
     * $items = DB\table('items')->paginate(perPage: 25);
     *
     * $items->getPagination()->getPages(); // Gets pagination pages as array
     * $items->getPagination()->getLinks(); // Gets pagination data as string
     * ```
     *
     * @throws MikroException When the PDO connection is not defined in the global $mikro array
     */
    function table(string $table, string $primaryKey = 'id'): object
    {
        return new class ($table, $primaryKey) {
            protected object $builder;
            protected array $model = [];
            protected array $attributes = [];

            public function __construct(
                protected string $table,
                protected string $primaryKey = 'id',
            ) {
                global $mikro;

                $this->builder = builder();
                $this->builder->table($this->table, '*');

                if (isset($mikro[MODELS][$this->table])) {
                    $this->model = $mikro[MODELS][$this->table];
                    $this->buildModel();
                }
            }

            public function getStatement(): \PDOStatement
            {
                $statement = connection()->prepare((string) $this->builder);

                foreach ($this->builder->getParameters() as $parameter) {
                    $statement->bindParam(...$parameter);
                }

                $statement->execute();

                return $statement;
            }

            public function get(): \Iterator
            {
                return Helper\arr(
                    $this->getStatement()->fetchAll(
                        \PDO::FETCH_PROPS_LATE | \PDO::FETCH_CLASS,
                        Helper\arr()::class
                    )
                )->transform([$this, 'applyGetter']);
            }

            public function find(?int $primaryKey = null): ?\Iterator
            {
                if ($primaryKey) {
                    $this->builder
                        ->where($this->primaryKey . '=:' . $this->primaryKey)
                        ->bindInt(':' . $this->primaryKey, $primaryKey);
                }

                $result = $this->getStatement()->fetch(\PDO::FETCH_NAMED);

                return $result ? $this->applyGetter(Helper\arr($result)) : null;
            }

            public function findOrFail(?int $primaryKey = null): \Iterator
            {
                $result = $this->find($primaryKey);

                if ($result === null) {
                    throw new DataNotFoundException();
                }

                return $result;
            }

            public function column(): mixed
            {
                return $this->getStatement()->fetchColumn();
            }

            public function count(): int
            {
                $this->builder->select('COUNT(*)');

                return $this->getStatement()->fetchColumn();
            }

            public function paginate(int $currentPage = 1, int $perPage = 10): \Iterator
            {
                $pagination = Pagination\paginate((clone $this)->count(), $currentPage, $perPage);

                $this->builder->limit("{$pagination['offset']},{$pagination['limit']}");

                return Helper\arr(
                    $this->getStatement()->fetchAll(
                        \PDO::FETCH_PROPS_LATE | \PDO::FETCH_CLASS,
                        Helper\arr()::class
                    )
                )->setPagination($pagination)->transform([$this, 'applyGetter']);
            }

            public function insert(): ?\PDOStatement
            {
                if ($this->applyEvent('inserting', $this->attributes) === false) {
                    return null;
                }

                $data = $this->getFillableAttributes();
                $keys = \trim(\implode(', ', \array_keys($data)), ', ');
                $values = \array_map(fn($item) => ':' . $item, \array_keys($data));

                $this->builder
                    ->insertInto(\sprintf('%s (%s)', $this->table, $keys))
                    ->valuesArray($values);

                foreach ($data as $key => $value) {
                    $this->builder->bind(':' . $key, $value);
                }

                $statement = $this->getStatement();

                $this->attributes[$this->primaryKey] = last_insert_id();
                $this->applyEvent('inserted', $this->attributes);

                return $statement;
            }

            public function update(): ?\PDOStatement
            {
                if ($this->applyEvent('updating', $this->attributes) === false) {
                    return null;
                }

                $data = $this->getFillableAttributes();

                $this->builder->update($this->table)->setArray(
                    Helper\arr($data)
                        ->mapWithKeys(fn($value, $key) => [$key => ':' . $key])
                        ->all()
                );

                foreach ($data as $key => $value) {
                    $this->builder->bind(':' . $key, $value);
                }

                $statement = $this->getStatement();

                $this->applyEvent('updated', $this->attributes);

                return $statement;
            }

            public function delete(): ?\PDOStatement
            {
                if ($this->applyEvent('deleting', $this->attributes) === false) {
                    return null;
                }

                $this->builder->deleteFrom($this->table);

                $statement = $this->getStatement();

                $this->applyEvent('deleted', $this->attributes);

                return $statement;
            }

            public function fill(array $attributes): self
            {
                foreach ($attributes as $key => $attribute) {
                    $attributes[$key] = $this->applySetter($key, $attribute);
                }

                $this->attributes = \array_replace($this->attributes, $attributes);

                return $this;
            }

            public function __call(string $method, array $args): mixed
            {
                $builderMethods = [
                    'bindBool', 'bindNull', 'bindInt', 'bindStr', 'bindStrNatl',
                    'bindStrChar', 'bindLob', 'bindStmt', 'innerJoin', 'crossJoin',
                    'leftJoin', 'rightJoin', 'outerJoin'
                ];

                if (
                    \method_exists($this->builder, $method) ||
                    \in_array($method, $builderMethods)
                ) {
                    $this->builder->{$method}(...$args);

                    return $this;
                }

                if (isset($this->model[$method]) && $this->model[$method] instanceof \Closure) {
                    return $this->model[$method]->call($this, ...$args);
                }

                throw new \Error("Call to undefined method {$method}");
            }

            public function __clone(): void
            {
                $this->builder = clone $this->builder;
            }

            protected function buildModel(): void
            {
                if (isset($this->model['table'])) {
                    $this->table = $this->model['table'];
                }

                if (isset($this->model['primary_key'])) {
                    $this->primaryKey = $this->model['primary_key'];
                }
            }

            public function applyGetter(object $item): object
            {
                foreach ($item as $key => $value) {
                    if (
                        isset($this->model['get_' . $key]) &&
                        $this->model['get_' . $key] instanceof \Closure
                    ) {
                        $item[$key] = $this->model['get_' . $key]->call($this, $value);
                    }
                }

                if (
                    isset($this->model['hidden']) &&
                    \is_array($this->model['hidden']) &&
                    ! empty($this->model['hidden'])
                ) {
                    foreach ($this->model['hidden'] as $hidden) {
                        if ($item->has($hidden)) {
                            $item->forget($hidden);
                        }
                    }
                }

                return $item;
            }

            public function applySetter(string $key, mixed $value): mixed
            {
                if (
                    isset($this->model['set_' . $key]) &&
                    $this->model['set_' . $key] instanceof \Closure
                ) {
                    return $this->model['set_' . $key]->call($this, $value);
                }

                return $value;
            }

            protected function applyEvent(string $event, mixed $data): mixed
            {
                if (
                    isset($this->model['event_' . $event]) &&
                    $this->model['event_' . $event] instanceof \Closure
                ) {
                    return $this->model['event_' . $event]->call($this, $data);
                }

                return $data;
            }

            protected function getFillableAttributes(): array
            {
                if (
                    isset($this->model['fillable']) &&
                    \is_array($this->model['fillable']) &&
                    ! empty($this->model['fillable'])
                ) {
                    $newAttributes = [];

                    foreach ($this->model['fillable'] as $fillable) {
                        if (isset($this->attributes[$fillable])) {
                            $newAttributes[$fillable] = $this->attributes[$fillable];
                        }
                    }

                    return $newAttributes;
                }

                return $this->attributes;
            }

            public function __get(string $key): mixed
            {
                return $this->attributes[$key] ?? $this->model[$key] ??
                    throw new \ErrorException('Undefined property $' . $key);
            }

            public function __set(string $key, mixed $value): void
            {
                $this->attributes[$key] = $this->applySetter($key, $value);
            }

            public function __isset(string $key): bool
            {
                return isset($this->attributes[$key]);
            }

            public function __unset(string $key): void
            {
                unset($this->attributes[$key]);
            }
        };
    }

    function builder(): object
    {
        return new class implements \Stringable {
            protected string $type = '';

            protected array $select = [
                'SELECT' => '',
                'FROM' => '',
                'JOIN' => '',
                'INNER JOIN' => '',
                'CROSS JOIN' => '',
                'LEFT JOIN' => '',
                'RIGHT JOIN' => '',
                'OUTER JOIN' => '',
                'WHERE' => '',
                'GROUP BY' => '',
                'HAVING' => '',
                'ORDER BY' => '',
                'LIMIT' => '',
            ];

            protected array $insert = [
                'INSERT INTO' => '',
                'SET' => '',
                'VALUES' => '',
                'ON DUPLICATE KEY UPDATE' => '',
            ];

            protected array $update = [
                'UPDATE' => '',
                'SET' => '',
                'WHERE' => '',
                'ORDER BY' => '',
                'LIMIT' => '',
            ];

            protected array $delete = [
                'DELETE FROM' => '',
                'WHERE' => '',
                'ORDER BY' => '',
                'LIMIT' => '',
            ];

            protected array $parameters = [];

            public static function make(): self
            {
                return new self();
            }

            public function select(string $select): self
            {
                $this->setType('select');

                $this->select['SELECT'] = $select . ' ';

                return $this;
            }

            public function from(string $from): self
            {
                $this->select['FROM'] .= $from . ' ';

                return $this;
            }

            public function table(string $from, ?string $select = null): self
            {
                if ($select) {
                    $this->select($select);
                } else {
                    $this->setType('select');
                }

                $this->from($from);

                return $this;
            }

            public function join(string $join): self
            {
                $this->select['JOIN'] .= $join . ' ';

                return $this;
            }

            public function where(string $where): self
            {
                $this->checkType();
                $this->checkAvailability('WHERE');

                $this->{$this->type}['WHERE'] .= $where . ' ';

                return $this;
            }

            public function groupBy(string $groupBy): self
            {
                $this->select['GROUP BY'] .= $groupBy . ' ';

                return $this;
            }

            public function having(string $having): self
            {
                $this->select['HAVING'] .= $having . ' ';

                return $this;
            }

            public function orderBy(string $orderBy): self
            {
                $this->checkType();
                $this->checkAvailability('ORDER BY');

                $this->{$this->type}['ORDER BY'] .= $orderBy . ' ';

                return $this;
            }

            public function limit(string $limit): self
            {
                $this->checkType();
                $this->checkAvailability('LIMIT');

                $this->{$this->type}['LIMIT'] .= $limit . ' ';

                return $this;
            }

            public function insertInto(string $insert): self
            {
                $this->setType('insert');

                $this->insert['INSERT INTO'] .= $insert . ' ';

                return $this;
            }

            public function values(string $values): self
            {
                $this->insert['VALUES'] .= $values . ' ';

                return $this;
            }

            public function valuesArray(array $values): self
            {
                $string = '';

                foreach ($values as $value) {
                    if (\is_numeric($value) || $value === '?' || \str_starts_with($value, ':')) {
                        $string .= $value . ', ';
                    } else {
                        $string .= "'{$value}', ";
                    }
                }

                return $this->values('(' . \trim($string, ', ') . ')');
            }

            public function onDuplicateKeyUpdate(string $onDuplicateKeyUpdate): self
            {
                $this->insert['ON DUPLICATE KEY UPDATE'] .= $onDuplicateKeyUpdate . ' ';

                return $this;
            }

            public function update(string $update): self
            {
                $this->setType('update');

                $this->update['UPDATE'] .= $update . ' ';

                return $this;
            }

            public function set(string $set): self
            {
                $this->checkType();
                $this->checkAvailability('SET');

                $this->{$this->type}['SET'] .= $set . ' ';

                return $this;
            }

            public function setArray(array $values): self
            {
                $string = '';

                foreach ($values as $key => $value) {
                    if (\is_numeric($value) || $value === '?' || \str_starts_with($value, ':')) {
                        $string .= "{$key}={$value}, ";
                    } else {
                        $string .= "{$key}='{$value}', ";
                    }
                }

                return $this->set(\trim($string, ', '));
            }

            public function deleteFrom(string $deleteFrom): self
            {
                $this->setType('delete');

                $this->delete['DELETE FROM'] .= $deleteFrom . ' ';

                return $this;
            }

            protected function setType(string $type): void
            {
                $oldType = $this->type;
                $this->type = $type;

                $common = ['SET', 'WHERE', 'ORDER BY', 'LIMIT'];

                foreach ($common as $item) {
                    if (! empty($this->{$oldType}[$item])) {
                        $this->{$type}[$item] = $this->{$oldType}[$item];
                    }
                }
            }

            protected function checkType(): void
            {
                if (empty($this->type)) {
                    throw new \Exception('Please declare `select`, `insertInto` or `update` methods first');
                }
            }

            protected function checkAvailability(string $statement): void
            {
                if (! isset($this->{$this->type}[$statement])) {
                    throw new \Exception(
                        \sprintf('%s is not available in %s query type', $statement, \strtoupper($this->type))
                    );
                }
            }

            public function bind(string|int $parameter, mixed $variable, int $type = \PDO::PARAM_STR): self
            {
                $this->parameters[$parameter] = [
                    'param' => $parameter, 'var' => $variable, 'type' => $type
                ];

                return $this;
            }

            public function binds(array $binds): self
            {
                foreach ($binds as $key => $value) {
                    $this->bind($key, $value);
                }

                return $this;
            }

            public function getParameters(): array
            {
                return $this->parameters;
            }

            public function __toString(): string
            {
                $this->checkType();

                $result = '';

                foreach ($this->{$this->type} as $key => $value) {
                    if (! empty(\trim($value))) {
                        $result .= \sprintf('%s %s ', $key, \trim($value));
                    }
                }

                return \trim($result);
            }

            public function __call(string $method, array $args): self
            {
                if (\str_ends_with($method, 'Join')) {
                    $type = \strtoupper(\str_replace('Join', '', $method));

                    if (isset($this->select[$type . ' JOIN'])) {
                        if (! isset($args[0])) {
                            throw new \ArgumentCountError("Too few arguments to method {$method}, 0 passed");
                        }

                        if (! is_string($args[0])) {
                            throw new \TypeError('Argument #1 must be of type string');
                        }

                        $this->select[$type . ' JOIN'] .= $args[0];

                        return $this;
                    }
                }

                if (\str_starts_with($method, 'bind')) {
                    $methods = ['Bool', 'Null', 'Int', 'Str', 'StrNatl', 'StrChar', 'Lob', 'Stmt'];
                    $last = \str_replace('bind', '', $method);

                    if (in_array($last, $methods)) {
                        if ($last === 'StrNatl' || $last === 'StrChar') {
                            $last = \str_replace('Str', 'Str_', $last);
                        }

                        $constant = 'PDO::PARAM_' . \strtoupper($last);

                        if (\count($args) !== 2) {
                            throw new \ArgumentCountError("Too few arguments to method {$method}");
                        }

                        if (! \is_string($args[0]) && ! \is_int($args[0])) {
                            throw new \TypeError('Argument #1 must be of type string|int');
                        }

                        return $this->bind($args[0], $args[1], \constant($constant));
                    }
                }

                throw new \Error("Call to undefined method {$method}");
            }
        };
    }

    /**
     * Defines table model and options
     *
     * {@inheritDoc} **Example:**
     * ```php
     * DB\model('items', [
     *     'table' => 'items', // sets table name
     *     'primary_key' => 'id', // sets table primary key
     *     'fillable' => ['fillable_field_name', 'title', 'foo', 'bar_id'], // set fillable attributes;
     *     'hidden' => ['password'], // set hidden fields
     *     'set_title' => fn($title) => strtoupper($title), // title attribute setter
     *     'get_foo' => fn($foo) => $foo === 'x' ? 'Active' : 'Inactive', // foo attribute getter
     *     'event_inserting' => fn(array $attributes) => $attributes,
     *     'event_inserting' => fn(array $attributes) => false,
     *     'event_inserted' => fn(array $attributes) => $attributes,
     *     'event_updating' => fn(array $attributes) => $attributes,
     *     'event_updated' => fn(array $attributes) => $attributes,
     *     'event_deleting' => fn(array $attributes) => $attributes,
     *     'event_deleted' => fn(array $attributes) => $attributes,
     *     'spesificMethod' => function () {
     *         $this->builder->select('id, name')->orderBy('id DESC');
     *
     *         return $this->get(); // get all
     *     }, // DB\table('items')->spesificMethod();
     *
     *     'findSlug' => function ($slug) {
     *         $this->builder->where('slug=:slug')->bind(':slug', $slug);
     *
     *         return $this->find(); // find slug
     *     } // DB\table('items')->findSlug($slug);
     * ]);
     * ```
     */
    function model(string $model, array $options): void
    {
        global $mikro;

        $mikro[MODELS][$model] = $options;
    }

    const MODELS = 'DB\MODELS';
};
