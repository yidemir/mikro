<?php

declare(strict_types=1);

namespace DB
{
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
                $this->type = 'select';
                $this->select['SELECT'] .= $select . ' ';

                return $this;
            }

            public function from(string $from): self
            {
                $this->select['FROM'] .= $from . ' ';

                return $this;
            }

            public function table(string $from, string $select = '*'): self
            {
                return $this->select($select)->from($from);
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
                $this->type = 'insert';
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
                $this->type = 'update';
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
                $this->type = 'delete';
                $this->delete['DELETE FROM'] .= $deleteFrom . ' ';

                return $this;
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
                        sprintf('%s is not available in %s query type', $statement, \strtoupper($this->type))
                    );
                }
            }

            public function bind(string|int $parameter, mixed $variable, int $type = \PDO::PARAM_STR)
            {
                $this->parameters[] = [
                    'param' => $parameter, 'var' => $variable, 'type' => $type
                ];

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

            public function __call(string $method, array $args)
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

    function model(string $model, array $options)
    {
        global $mikro;

        $mikro[MODELS][$model] = $options;
    }

    const MODELS = 'DB\MODELS';
};
