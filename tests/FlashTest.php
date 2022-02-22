<?php

declare(strict_types=1);

namespace Mikro\Tests;

use PHPUnit\Framework\TestCase;

class FlashTest extends TestCase
{
    public function testInvalidRandomValue()
    {
        \Flash\set(\Flash\TYPE_ERROR, 'message');

        $this->assertTrue($_SESSION['__flash_error'][0] === 'message');
        $this->assertIsArray(\Flash\get(\Flash\TYPE_ERROR));
        $this->assertTrue(empty(\Flash\get(\Flash\TYPE_ERROR)));
        $this->assertTrue(! isset($_SESSION['__flash_error']));

        \Flash\success('message');
        \Flash\success('another message');

        $this->assertCount(2, \Flash\get(\Flash\TYPE_SUCCESS));
        $this->assertNull(\Flash\get(\Flash\TYPE_SUCCESS));
    }
}
