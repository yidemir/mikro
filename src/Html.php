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
     *
     * New feature: Invokable tag
     * Always returns string, instead of \Stringable
     * ```php
     * Html\tag('div')->class('foo')('Content'); // <div class="foo">content</div>
     * Html\tag('span')('Text', ['class' => 'bg-light']); // <span class="bg-light">Text</span>
     * Html\tag('ul', Html\tag('li')('Item'))(); // <ul><li>Item</li></ul>
     * ```
     */
    function tag(string $name, mixed $content = '', array $attributes = []): object
    {
        return new class ($name, $content, $attributes) implements \Stringable {
            public function __construct(
                protected string $name,
                protected mixed $content,
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
                $attribute = $args[0] ?? null;
                $append = $args[1] ?? null;

                if (isset($this->attributes[$name]) && $append === true) {
                    $this->attributes[$name] .= $attribute;
                } else {
                    $this->attributes[$name] = $attribute;
                }

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

            public function __invoke(mixed $content = '', array $attributes = []): string
            {
                if (\is_array($content)) {
                    $content = \implode('', \array_map(fn($tag) => (string) $tag, $content));
                }

                $this->content = (string) $content;
                $this->attributes = \array_replace($this->attributes, $attributes);

                return $this->__toString();
            }
        };
    }
};
