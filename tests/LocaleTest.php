<?php

declare(strict_types=1);

namespace Mikro\Tests;

use PHPUnit\Framework\TestCase;

class LocaleTest extends TestCase
{
    public function testPhrases()
    {
        global $mikro;

        \Locale\set('en');
        $mikro[\Locale\DATA]['en'] = ['foo' => 'bar'];

        $this->assertSame(\Locale\t('Hello world'), 'Hello world');
        $this->assertSame(\Locale\t('foo'), $mikro[\Locale\DATA]['en']['foo']);

        unset($mikro);
    }

    public function testPhrasesWithLocaleFile()
    {
        global $mikro;

        $mikro[\Locale\PATH] = __DIR__;

        file_put_contents(
            $f = __DIR__ . '/tr.php',
            '<?php return ["Hello" => "Merhaba"];'
        );

        \Locale\set('tr');

        $this->assertSame(\Locale\t('Hello'), 'Merhaba');
        $this->assertSame(\Locale\t('Mikro'), 'Mikro');

        unlink($f);
    }
}
