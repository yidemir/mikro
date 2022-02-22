<?php

declare(strict_types=1);

namespace Mikro\Tests;

use PHPUnit\Framework\TestCase;

class ErrorTest extends TestCase
{
    public function testErrorsToException()
    {
        $this->expectException(\ErrorException::class);

        \Error\to_exception();

        trigger_error('Error!');
    }
}
