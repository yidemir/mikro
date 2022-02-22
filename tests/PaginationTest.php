<?php

declare(strict_types=1);

namespace Mikro\Tests;

use PHPUnit\Framework\TestCase;

class PaginationTest extends TestCase
{
    public function testPaginationWith100Items()
    {
        $data = range(1, 100);

        $paginate = \Pagination\paginate(count($data), 1, 10);

        $this->assertIsArray($paginate);
        $this->assertArrayHasKey('offset', $paginate);
        $this->assertArrayHasKey('limit', $paginate);
        $this->assertArrayHasKey('current_page', $paginate);
        $this->assertArrayHasKey('next_page', $paginate);
        $this->assertArrayHasKey('previous_page', $paginate);
        $this->assertArrayHasKey('total_page', $paginate);

        $this->assertSame($paginate['offset'], 0);
        $this->assertSame($paginate['limit'], 10);
        $this->assertSame($paginate['current_page'], 1);
        $this->assertSame($paginate['next_page'], 2);
        $this->assertSame($paginate['previous_page'], 1);
        $this->assertSame($paginate['total_page'], 10);

        $paginate = \Pagination\paginate(count($data), 7, 10);

        $this->assertSame($paginate['offset'], 60);
        $this->assertSame($paginate['limit'], 10);
        $this->assertSame($paginate['current_page'], 7);
        $this->assertSame($paginate['next_page'], 8);
        $this->assertSame($paginate['previous_page'], 6);
        $this->assertSame($paginate['total_page'], 10);
    }
}
