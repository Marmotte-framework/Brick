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

use Marmotte\Brick\Bricks\BrickLoader;
use Marmotte\Brick\Bricks\BrickManager;
use Marmotte\Brick\Cache\CacheManager;
use Marmotte\Brick\Exceptions\PackageContainsNoBrickException;
use Marmotte\Brick\Exceptions\PackageContainsSeveralBrickException;
use Throwable;

class BrickLoaderTest extends BrickTestCase
{
    public function testCannotLoadFromDirWithoutBrick(): void
    {
        try {
            $this->brick_loader->loadFromDir(__DIR__ . '/Fixtures/InvalidBrick');

            self::fail();
        } catch (Throwable $e) {
            self::assertInstanceOf(PackageContainsNoBrickException::class, $e);
        }
    }

    public function testCannotLoadFromDirWithSeveralBrick(): void
    {
        try {
            $this->brick_loader->loadFromDir(__DIR__ . '/Fixtures/InvalidBrick2');

            self::fail();
        } catch (Throwable $e) {
            self::assertInstanceOf(PackageContainsSeveralBrickException::class, $e);
        }
    }

    public function testCanLoadFromValidDir(): void
    {
        try {
            $this->brick_loader->loadFromDir(__DIR__ . '/Fixtures/Brick');
        } catch (Throwable $e) {
            self::fail($e->getMessage());
        }

        self::assertCount(1, $this->brick_manager->getBricks());
    }

    public function testCanLoadFromCache(): void
    {
        try {
            $this->brick_loader->loadFromDir(__DIR__ . '/Fixtures/Brick');
        } catch (Throwable $e) {
            self::fail($e->getMessage());
        }

        $bricks = $this->brick_manager->getBricks();
        self::assertCount(1, $bricks);


        $br = new BrickManager();
        $bl = new BrickLoader(
            $br,
            $this->cache_manager
        );
        try {
            $bl->loadFromCache();
        } catch (Throwable $e) {
            self::fail($e->getMessage());
        }

        $actual = $br->getBricks();
        self::assertCount(1, $actual);
        self::assertEqualsCanonicalizing($bricks, $actual);
    }
}
