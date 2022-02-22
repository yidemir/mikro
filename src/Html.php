<?php

declare(strict_types=1);

namespace Html
{
    /**
     * Creates a Html tag
     *
     * {@inheritDoc} **Example:**
     * ```php
     * echo Html\tag('br'); // <br>
     * echo Html\tag('table') // <table></table>
     * echo Html\tag('p', 'Hello world!'); // <p>Hello world!</p>
     * echo Html\tag('p', 'Hey!', ['class' => 'hey']); // <p class="hey">Hey!</p>
     * echo Html\tag('p', 'Hey!')->class('hey'); // <p class="hey">Hey!</p>
     * echo Html\tag('p', 'Hey!', ['PascalCase' => 'true'])->snakeCase('true')->kebabCase('false');
     * // <p PascalCase="true" snake-case="true" kebab-case="false">Hey!</p>
     *
     * echo Html\tag('div', [
     *     Html\tag('a', 'Link')->href('http://url.com')->style('text-decoration:none;'),
     *     Html'tag('a', 'Link')->href('http://url.com')->style(['text-decoration' => 'none'])
     * ]); // same result
     * ```
     */
    function tag(string $name, \Stringable|string|array $content = '', array $attributes = []): object
    {
        return new class ($name, $content, $attributes) implements \Stringable {
            public function __construct(
                protected string $name,
                protected \Stringable|string|array $content,
                protected array $attributes = []
            ) {
                if (\is_array($content)) {
                    $content = \implode('', \array_map(fn($tag) => (string) $tag, $content));
                }

                $this->content = (string) $content;
            }

            public function __call(string $name, array $args): self
            {
                $name = \strtolower(\preg_replace('/(?<!^)[A-Z]/', '-$0', $name));
                $this->attributes[$name] = $args[0] ?? null;

                return $this;
            }

            public function __toString(): string
            {
                $attributes = '';

                foreach ($this->attributes as $key => $value) {
                    if (\is_array($value)) {
                        $value = \implode('', \array_map(function ($key, $value) {
                            return "{$key}:{$value};";
                        }, \array_keys($value), \array_values($value)));
                    }

                    if ($value === null) {
                        $attributes .= "{$key} ";
                    } elseif ($value === false) {
                        // pass
                    } else {
                        $attributes .= \sprintf('%s="%s" ', $key, $value);
                    }
                }

                $attributes = empty($attributes) ? '' : ' ' . \trim($attributes);
                $tag = \sprintf('<%s%s>', $this->name, $attributes);

                if ($this->content === "0" || $this->content) {
                    $tag .= $this->content;
                }

                $selfCloseTags = [
                    'br',
                    'hr',
                    'col',
                    'img',
                    'wbr',
                    'area',
                    'base',
                    'link',
                    'meta',
                    'embed',
                    'input',
                    'param',
                    'track',
                    'source',
                ];

                if (! \in_array($this->name, $selfCloseTags)) {
                    $tag .= \sprintf('</%s>', $this->name);
                }

                return $tag;
            }

            public function __set(string $attribute, mixed $value): void
            {
                $this->attributes[$attribute] = $value;
            }

            public function __get(string $attribute): mixed
            {
                return $this->attributes[$attribute] ?? null;
            }

            public function __isset(string $attribute): bool
            {
                return \array_key_exists($attribute, $this->attributes);
            }
        };
    }
};
