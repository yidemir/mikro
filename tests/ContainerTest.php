<?php

declare(strict_types=1);

namespace Mikro\Tests;

use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
    public function testSetAndCheckContainerItem()
    {
        \Container\set('name', 'value');

        $this->assertTrue(\Container\has('name'));
        $this->assertSame(\Container\get('name'), 'value');
    }

    public function testClosureAndValue()
    {
        \Container\set('test', function (string $parameter): string {
            return $parameter;
        });

        $this->assertInstanceOf(\Closure::class, \Container\get('test'));
        $this->assertTrue(is_string(\Container\value('test', ['string parameter'])));
    }

    public function testObjectIsNotSame()
    {
        \Container\set('object', function () {
            return new \stdClass();
        });

        $this->assertNotSame(\Container\value('object'), \Container\value('object'));
    }

    public function testSingletonItemIsSame()
    {
        \Container\singleton('singleton', function () {
            return new \stdClass();
        });

        $this->assertSame(\Container\value('singleton'), \Container\value('singleton'));
    }
}
