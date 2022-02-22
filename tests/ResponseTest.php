<?php

declare(strict_types=1);

namespace Mikro\Tests;

use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    public function testResponseOutput()
    {
        ob_start();
        $this->assertTrue(\Response\output('content') === 1);
        ob_end_clean();

        ob_start();
        \Response\output('content');
        $this->assertEquals(ob_get_clean(), 'content');
    }
}
