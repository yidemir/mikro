<?php

namespace validator;

function validate(array $data, array $parseableRules): array
{
    $errors = [];

    foreach ($parseableRules as $field => $rules) {
        foreach ($rules as $rule => $message) {
            $callback = eval('return function($v){return '. $rule .';};');

            if ($callback(@$data[$field])) {
                $errors[$field] = $message;
            }
        }
    }

    return $errors;
}
