<?php

declare(strict_types=1);

namespace Mikro\Tests;

use PHPUnit\Framework\TestCase;

class ConsoleTest extends TestCase
{
    public function testWriteMethods()
    {
        \Console\write('foo');
        $this->expectOutputString("foo\n");
    }
}
