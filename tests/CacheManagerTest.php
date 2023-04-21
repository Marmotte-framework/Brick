<?php
/**
 * MIT License
 *
 * Copyright (c) 2023-Present Kevin Traini
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

declare(strict_types=1);

namespace Marmotte\Brick;

use Marmotte\Brick\Cache\CacheManager;
use Marmotte\Brick\Fixtures\ExampleObject;

require_once __DIR__ . '/../vendor/autoload.php';


class CacheManagerTest extends BrickTestCase
{
    public function testCanSave(): void
    {
        CacheManager::instance()->save('', 'test', [
            'foo' => 'bar',
        ]);

        self::assertTrue(file_exists(__DIR__ . '/cache'));
        self::assertCount(1, glob(__DIR__ . '/cache'));
    }

    public function testCannotLoad(): void
    {
        $res = CacheManager::instance()->load('', 'gbleskefe');

        self::assertNull($res);
    }

    public function testCanSaveThenLoadArray(): void
    {
        $content = [
            'pi' => 3.14
        ];

        CacheManager::instance()->save('', 'array', $content);

        $res = CacheManager::instance()->load('', 'array');

        self::assertNotNull($res);
        self::assertIsArray($res);
        self::assertEquals($content, $res);
    }

    public function testCanSaveThenLoadObject(): void
    {
        $object = new ExampleObject(
            'bar',
            3.14,
            true,
            42
        );

        CacheManager::instance()->save('', ExampleObject::class, $object);

        $res = CacheManager::instance()->load('', ExampleObject::class);

        self::assertNotNull($res);
        self::assertIsObject($res);
        self::assertInstanceOf(ExampleObject::class, $res);
        self::assertEquals($object, $res);
    }

    public function testInstanceReturnsSameInstance(): void
    {
        self::assertSame(CacheManager::instance(), CacheManager::instance());
    }
}
