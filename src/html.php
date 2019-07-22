<?php
declare(strict_types=1);

namespace html;

/**
 * @param  string|array $content
 * @return object
 */
function tag(string $name, $content = '', array $attributes = [])
{
    return new class ($name, $content, $attributes) {

        /** @var string */
        protected $name;

        /** @var string */
        protected $content;

        /** @var array */
        protected $attributes;

        /**
         * @param string|array $content
         */
        public function __construct(
            string $name,
            $content,
            array $attributes = []
        )
        {
            $this->name = $name;

            if (\is_array($content)) {
                $content = \implode('', \array_map(function($tag) {
                    return (string) $tag;
                }, $content));
            }

            $this->content = (string) $content;
            $this->attributes = $attributes;
        }

        public function __call(string $name, array $args)
        {
            $this->attributes[$name] = $args[0] ?? null;

            return $this;
        }

        public function __toString()
        {
            $attributes = '';

            foreach ($this->attributes as $key => $value) {
                if (\is_array($value)) {
                    $value = \implode('', \array_map(function($key, $value) {
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
                'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 
                'link', 'meta', 'param', 'source', 'track', 'wbr'
            ];

            if (!\in_array($this->name, $selfCloseTags)) {
                $tag .= \sprintf('</%s>', $this->name);
            }

            return $tag;
        }

        public function __set(string $attribute, $value)
        {
            $this->attributes[$attribute] = $value;
        }

        public function __get(string $attribute)
        {
            return $this->attributes[$attribute] ?? null;
        }

        public function __isset(string $attribute)
        {
            return \array_key_exists($attribute, $this->attributes);
        }
    };
}

/**
 * @param  string|array $content
 * @return object
 */
function div($content = '', array $attributes = [])
{
    return tag('div', $content, $attributes);
}

/**
 * @param  string|array $content
 * @return object
 */
function p($content = '', array $attributes = [])
{
    return tag('p', $content, $attributes);
}

/**
 * @param  string|array $content
 * @return object
 */
function span($content = '', array $attributes = [])
{
    return tag('span', $content, $attributes);
}

/**
 * @param  string|array $content
 * @return object
 */
function label($content = '', ?string $for = null, array $attributes = [])
{
    if ($for !== null) {
        $attributes['for'] = $for;
    }

    return tag('label', $content, $attributes);
}

/**
 * @return object
 */
function input(string $type, ?string $name = null, array $attributes = [])
{
    $attributes['type'] = $type;

    if ($name !== null) {
        $attributes['name'] = $name;
        $attributes['id'] = $name;
    }

    return tag('input', '', $attributes);
}

/**
 * @param  string|array $content
 * @return object
 */
function textarea($content = '', ?string $name = null, array $attributes = [])
{
    if ($name !== null) {
        $attributes['name'] = $name;
        $attributes['id'] = $name;
    }

    return tag('textarea', $content, $attributes);
}
/**
 * @return object
 */
function select(array $options = [], ?string $name = null, array $attributes = [])
{
    $selectedOption = \array_key_exists('selectedOption', $attributes) ?
        $attributes['selectedOption'] : null;

    unset($attributes['selectedOption']);

    $optionAttributes = \array_key_exists('optionAttributes', $attributes) ?
        $attributes['optionAttributes'] : [];

    unset($attributes['optionAttributes']);

    $options = \array_map(function($key, $value) use ($selectedOption, $optionAttributes) {
        $attributes = ['value' => $key];

        if ($selectedOption === $key) {
            $attributes['selected'] = null;
        }

        if (\array_key_exists($key, $optionAttributes)) {
            $attributes = \array_merge($attributes, (array) $optionAttributes[$key]);
        }

        return tag('option', $value, $attributes);
    }, \array_keys($options), \array_values($options));

    if ($name !== null) {
        $attributes['name'] = $name;
        $attributes['id'] = $name;
    }

    return tag('select', $options, $attributes);
}

/**
 * @param  string|array $content
 * @return object
 */
function button($content = '', array $attributes = [])
{
    return tag('button', $content, $attributes);
}

/**
 * @param  string|array $content
 * @return object
 */
function a($content = '', ?string $href = null, array $attributes = [])
{
    if ($href !== null) {
        $attributes['href'] = $href;
    }

    return tag('a', $content, $attributes);
}
