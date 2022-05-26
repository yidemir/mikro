<?php

declare(strict_types=1);

namespace Mikro\Tests;

use PHPUnit\Framework\TestCase;

class ErrorTest extends TestCase
{
    public function testErrorsToException()
    {
        $this->expectException(\ErrorException::class);
        $this->expectExceptionMessage('Error!');

        \Error\handler();

        trigger_error('Error!');
    }
}
