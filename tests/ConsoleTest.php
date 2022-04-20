<?php

declare(strict_types=1);

namespace Mikro\Tests;

use PHPUnit\Framework\TestCase;

class ConsoleTest extends TestCase
{
    public function testWriteMethods()
    {
        $this->expectOutputString("foo\r\n");

        \Console\write('foo');
    }
}
