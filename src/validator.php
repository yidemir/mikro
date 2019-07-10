<?php
declare(strict_types=1);

namespace validator;

use Closure;
use stdClass;

function collection(?string $rule = null, ?Closure $callback = null): array
{
    static $collection;

    if ($collection === null) {
        return [
            'required' => function($key, $values) {
                return isset($values[$key]) && !empty($values[$key]);
            },
            'nullable' => function($key, &$values) {
                $values['nullables'][] = $key;
            },
            'email' => function($key, $values) {
                return (bool) \filter_var((string) @$values[$key], \FILTER_VALIDATE_EMAIL);
            },
            'same' => function($key, $values, $param) {
                return @$values[$key] === $param;
            },
            'maxlen' => function($key, $values, $param) {
                return \mb_strlen((string) @$values[$key]) <= $param;
            },
            'minlen' => function($key, $values, $param) {
                return \mb_strlen((string) @$values[$key]) >= $param;
            },
            'max' => function($key, $values, $param) {
                return @$values[$key] <= $param;
            },
            'min' => function($key, $values, $param) {
                return @$values[$key] >= $param;
            },
            'float' => function($key, $values) {
                return (bool) \filter_var((string) @$values[$key], FILTER_VALIDATE_FLOAT);
            },
            'numeric' => function($key, $values) {
                return \is_numeric(@$values[$key]);
            },
            'alpha' => function($key, $values) {
                return \ctype_alpha(@$values[$key]);
            },
            'alnum' => function($key, $values) {
                return \ctype_alnum(@$values[$key]);
            },
            'time' => function($key, $values) {
                return \date_parse((string) @$values[$key])['error_count'] <= 0;
            },
            'ip' => function($key, $values) {
                return (bool) \filter_var((string) @$values[$key], \FILTER_VALIDATE_IP);
            },
            'url' => function($key, $values) {
                return (bool) \filter_var((string) @$values[$key], \FILTER_VALIDATE_URL);
            },
            'regex' => function($key, $values, $param) {
                return (bool) \preg_match((string) $param, (string) @$values[$key]);
            },
            'lower' => function($key, $values) {
                return \ctype_lower(@$values[$key]);
            },
            'upper' => function($key, $values) {
                return \ctype_upper(@$values[$key]);
            },
            'in' => function($key, $values, $param) {
                return \in_array(@$values[$key], \explode(',', (string) $param));
            },
            'notin' => function($key, $values, $param) {
                return !\in_array(@$values[$key], \explode(',', (string) $param));
            }
        ];
    }

    if ($rule !== null && $callback !== null) {
        $collection[$rule] = $callback;
    }

    return $collection;
}

function messages(?string $rule = null, ?string $message = null): array
{
    static $messages;

    if ($messages === null) {
        $messages = [
            'required' => '%s alanı gereklidir',
            'email' => '%s alanı geçerli bir e-posta adresi değil',
            'same' => '%s alanı diğer alanla aynı değere sahip olmalıdır',
            'maxlen' => '%s alanı çok uzun, en fazla %s karakter olabilir',
            'minlen' => '%s alanı çok kısa, en az %s karakter olabilir',
            'max' => '%s alanı çok fazla, en fazla %s olmalıdır',
            'min' => '%s alanı çok az, en az %s olmalıdır',
            'float' => '%s alanı geçerli bir ondalık sayı olmalıdır',
            'numeric' => '%s alanı yalnızca rakamlardan oluşabilir',
            'alpha' => '%s alanı yalnızca harflerden oluşabilir',
            'alnum' => '%s alanı yalnızca harf ve rakamlardan oluşabilir',
            'time' => '%s alanı geçerli bir zaman değeri olmalıdır',
            'ip' => '%s alanı geçerli bir IP adresi olmalıdır',
            'url' => '%s alanı geçerli bir URL olmalıdır',
            'regex' => '%s alanı geçerli değil',
            'lower' => '%s alanı yalnızca küçük harflerden oluşmalıdır',
            'upper' => '%s alanı yalnızca büyük harflerden oluşmalıdır',
            'in' => '%s alanı belirlenen öğelerden birisi olmalıdır',
            'notin' => '%s alan belirlenen öğelerden birisi olmamalıdır'
        ];
    }

    if ($rule !== null && $message !== null) {
        $messages[$rule] = $message;
    } 

    return $messages;
}

function validate(array $values, array $rules): stdClass
{
    $validator = new stdClass;
    $validator->errors = [];
    $validator->errorByFields = [];

    foreach (parse($rules) as $field => $validation) {
        foreach ($validation['rules'] as $ruleData) {
            [$rule, $param] = $ruleData;
            $check = collection()[$rule]($field, $values, $param);
            $nullables = $values['nullables'] ?? [];

            if (\in_array($field, $nullables)) {
                if (
                    isset($values[$field]) && 
                    $values[$field] !== '' && 
                    $rule !== 'nullable' && 
                    !$check
                ) {
                    $validator->errors[] = \sprintf(
                        messages()[$rule], $validation['name'], $param
                    );
                    $validator->errorByFields[$field][] = \sprintf(
                        messages()[$rule], $validation['name'], $param
                    );
                }
            } else {
                if (!$check) {
                    $validator->errors[] = \sprintf(
                        messages()[$rule], $validation['name'], $param
                    );
                    $validator->errorByFields[$field][] = \sprintf(
                        messages()[$rule], $validation['name'], $param
                    );
                }
            }
        }
    }

    unset($values['nullables']);
    $validator->success = empty($validator->errors);
    $validator->fails = !$validator->success;
    $validator->values = \array_filter($values, function($key) use ($validator) {
        return !\array_key_exists($key, $validator->errorByFields);
    }, \ARRAY_FILTER_USE_KEY);

    return $validator;
}

function parse(array $parseableRules): array
{
    $parsedRules = [];

    foreach ($parseableRules as $field => $rules) {
        if (!\is_array($rules)) {
            $rules = \explode('|', $rules);
        }

        $field = \explode('|', $field);
        $name = $field[1] ?? $field[0];
        $field = $field[0];

        foreach ($rules as $rule) {
            $rule = \explode(':', $rule);
            $param = $rule[1] ?? null;
            $rule = $rule[0];

            if (!\array_key_exists($rule, collection())) {
                throw new \Exception('Kural mevcut değil: ' . $rule);
            }

            $parsedRules[$field]['name'] = $name;
            $parsedRules[$field]['rules'][] = [$rule, $param];
        }
    }

    return $parsedRules;
}
