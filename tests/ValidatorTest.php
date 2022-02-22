<?php

declare(strict_types=1);

namespace Mikro\Tests;

use PHPUnit\Framework\TestCase;

use function Validator\validate;

class ValidatorTest extends TestCase
{
    public function testValidateMethodSimpleData()
    {
        $this->assertFalse(validate([], 'name', 'isset'));
        $this->assertTrue(validate(['name' => 'foo'], 'name', 'isset'));
        $this->assertTrue(validate(['foo' => 'bar'], 'foo', fn($value) => strlen($value) <= 3));
    }

    public function testValidateMethodWithNotClause()
    {
        $this->assertTrue(validate([], 'name', '!isset'));
        $this->assertFalse(validate(['name' => 'foo'], 'name', '!isset'));
    }

    public function testValidateMethodWithSomeFunctions()
    {
        $data = [
            'name' => '',
            'age' => 25,
            'value' => '127',
            'email' => 'foo@bar.baz',
        ];

        $this->assertTrue(validate($data, 'name', 'isset|empty'));
        $this->assertFalse(validate($data, 'name', 'isset|!empty'));
        $this->assertFalse(validate($data, 'name', '!isset|!empty'));

        $this->assertTrue(validate($data, 'age', 'isset|is_numeric'));
        $this->assertTrue(validate($data, 'age', [
            'isset',
            'is_numeric',
            fn($val) => $val > 18
        ]));
        $this->assertTrue(validate($data, 'value', 'isset|is_numeric'));

        $this->assertTrue(validate($data, 'email', 'filter_var:' . FILTER_VALIDATE_EMAIL));
        $this->assertFalse(validate($data, 'email', '!filter_var:' . FILTER_VALIDATE_EMAIL));
    }

    public function testCheckValidateAllMethod()
    {
        $data = [
            'name' => '',
            'age' => 25,
            'value' => '127',
            'email' => 'foo@bar.baz',
        ];

        $validate = \Validator\validate_all($data, [
            'name' => 'isset|!empty',
            'age' => 'isset|is_numeric',
            'value' => 'isset|is_numeric',
            'email' => 'isset|filter_var:' . FILTER_VALIDATE_EMAIL
        ]);

        $this->assertIsArray($validate);

        foreach ($data as $key => $value) {
            $this->assertArrayHasKey($key, $data);
            $this->assertIsBool($validate[$key]);
        }
    }

    public function testCheckIsValidatedAllMethod()
    {
        $data = [
            'name' => '',
            'age' => 25,
            'value' => '127',
            'email' => 'foo@bar.baz',
        ];

        $validated = \Validator\is_validated_all($data, [
            'name' => 'isset|!empty',
            'age' => 'isset|is_numeric',
            'value' => 'isset|is_numeric',
            'email' => 'isset|filter_var:' . FILTER_VALIDATE_EMAIL
        ]);

        $this->assertIsBool($validated);
    }
}
