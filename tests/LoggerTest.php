<?php

declare(strict_types=1);

namespace Mikro\Tests;

use PHPUnit\Framework\TestCase;

class LoggerTest extends TestCase
{
    public function setUp(): void
    {
        global $mikro;

        $mikro[\Logger\PATH] = __DIR__;
    }

    public function testCheckLoggerPathIsPresent()
    {
        $this->assertSame(\Logger\path(), __DIR__);
    }

    public function testFormatMethod()
    {
        $this->assertSame(
            \Logger\format(\Logger\LEVEL_EMERGENCY, 'test', ['foo' => 'bar']),
            '[' . \date('Y-m-d H:i:s') . '] ' . \mb_strtoupper(\Logger\LEVEL_EMERGENCY) . ': test {"foo":"bar"}' . \PHP_EOL
        );
    }

    public function testWriteMethod()
    {
        \Logger\log(\Logger\LEVEL_ERROR, 'message', ['context']);
        $format = \Logger\format(\Logger\LEVEL_ERROR, 'message', ['context']);

        $data = file(\Logger\create_file());
        $lastLine = $data[count($data) - 1];

        $this->assertEquals($format, $lastLine);
    }

    public function tearDown(): void
    {
        unlink(\Logger\create_file());
    }
}
