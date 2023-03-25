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

namespace Marmot\Brick;

use Marmot\Brick\Bricks\BrickLoader;
use Marmot\Brick\Bricks\BrickManager;
use Marmot\Brick\Fixtures\Brick\AService;
use Marmot\Brick\Services\ServiceManager;

require_once __DIR__ . '/../vendor/autoload.php';

class BrickManagerTest extends BrickTestCase
{
    public function testCanLoadBrick()
    {
        $bl = new BrickLoader();
        try {
            $bl->loadFromDir(__DIR__ . '/Fixtures/Brick');
            BrickManager::instance()->initialize(__DIR__ . '/Fixtures');
        } catch (\Throwable $e) {
            self::fail($e);
        }

        self::assertTrue(ServiceManager::instance()->hasService(AService::class));
        self::assertTrue(ServiceManager::instance()->hasService(ServiceManager::class));
        self::assertTrue(ServiceManager::instance()->hasService(BrickManager::class));

        $service = ServiceManager::instance()->getService(AService::class);
        self::assertInstanceOf(AService::class, $service);
        $config = $service->getConfig();
        self::assertIsArray($config);
        self::assertEquals(['hello' => 'world!'], $config);
    }
}
