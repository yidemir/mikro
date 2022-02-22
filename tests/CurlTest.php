<?php

declare(strict_types=1);

namespace Mikro\Tests;

use PHPUnit\Framework\TestCase;

class CurlTest extends TestCase
{
    public function testCurlGetMethod()
    {
        $curl = \Curl\make('https://jsonplaceholder.typicode.com/posts', 'GET')->exec();

        $this->assertTrue(is_string((string) $curl));
    }

    public function testCurlPostMethod()
    {
        $curl = \Curl\make('https://jsonplaceholder.typicode.com/posts')
            ->method('post')
            ->asForm()
            ->data([
                'title' => 'foo',
                'body' => 'bar',
                'userId' => 1
            ])
            ->exec();

        $this->assertArrayHasKey('title', (array) $curl->json());
        $this->assertSame($curl->json()['title'] ?? null, 'foo');
    }

    public function testCurlPutMethod()
    {
        $curl = \Curl\make('https://jsonplaceholder.typicode.com/posts/1')
            ->method('put')
            ->asForm()
            ->data([
                'id' => 1,
                'title' => 'foo',
                'body' => 'bar',
                'userId' => 1
            ])
            ->exec();

        $this->assertArrayHasKey('title', (array) $curl->json());
        $this->assertSame($curl->json()['title'] ?? null, 'foo');
    }

    public function testCurlJsonMethod()
    {
        $response = \Curl\make('https://jsonplaceholder.typicode.com/posts')->json();

        $this->assertCount(100, (array) $response);
    }

    public function testCurlInfoMethod()
    {
        $curl = \Curl\make($url = 'https://jsonplaceholder.typicode.com/posts')->exec();

        $this->assertTrue(is_array($curl->getInfo()));
        $this->assertArrayHasKey('url', $curl->getInfo());
        $this->assertSame($url, $curl->getInfo('url'));
        $this->assertSame(200, $curl->getInfo('http_code'));
    }

    public function testCurlRequestIsOk()
    {
        $curl = \Curl\make('https://jsonplaceholder.typicode.com/posts')->exec();

        $this->assertTrue($curl->isOk());
        $this->assertFalse($curl->isRedirect());
        $this->assertFalse($curl->isFailed());
        $this->assertFalse($curl->isServerError());
        $this->assertFalse($curl->isClientError());
    }

    public function testCurlRequestIsFailed()
    {
        $curl = \Curl\make('https://jsonplaceholder.typicode.com/posts~')->exec();

        $this->assertTrue($curl->isFailed());
        $this->assertTrue($curl->isClientError());
        $this->assertFalse($curl->isServerError());
        $this->assertFalse($curl->isRedirect());
        $this->assertFalse($curl->isOk());
    }
}
