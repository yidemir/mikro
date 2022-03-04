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

    public function testPaginationLinks()
    {
        $data = range(1, 100);

        $paginate = \Pagination\paginate(count($data), 1, 10);
        $links = \Pagination\links($paginate);

        $this->assertStringContainsString('<nav>', $links);
        $this->assertStringContainsString('<span>', $links);
        $this->assertStringContainsString('<a href="?page=1">1</a>', $links);
        $this->assertStringContainsString('<span><a href="?page=1">1</a></span>', $links);
        $this->assertStringContainsString('<a href="?page=10">10</a>', $links);
    }

    public function testStyledPaginationLinks()
    {
        $data = range(1, 100);

        $paginate = \Pagination\paginate(count($data), 1, 10);
        $links = \Pagination\links($paginate, $options = [
            'container-tag' => 'ul',
            'item-tag' => 'li',
            'styles' => [
                'container' => ['list-style-type' => 'none'],
                'link' => ['margin' => '1px'],
                'link-current' => ['font-weight' => 'bold'],
                'item' => ['padding' => '5px'],
                'item-current' => ['font-weight' => 'bold']
            ]
        ]);

        //container
        $this->assertStringContainsString('<ul style="list-style-type:none;">', $links);

        // item
        $this->assertStringContainsString('<li style="padding:5px;">', $links);

        // current item
        $this->assertStringContainsString('<li style="font-weight:bold;padding:5px;">', $links);

        // link
        $this->assertStringContainsString('<a style="margin:1px;" href="?page=2">2</a>', $links);

        // current link
        $this->assertStringContainsString('<a style="font-weight:bold;margin:1px;" href="?page=1">1</a>', $links);
    }
}
