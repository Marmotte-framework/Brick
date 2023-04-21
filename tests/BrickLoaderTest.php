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
use Marmotte\Brick\Exceptions\PackageContainsNoBrickException;
use Marmotte\Brick\Exceptions\PackageContainsSeveralBrickException;
use Throwable;

class BrickLoaderTest extends BrickTestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        BrickManager::flush();
    }

    public function testCannotLoadFromDirWithoutBrick()
    {
        $bl = new BrickLoader();
        try {
            $bl->loadFromDir(__DIR__ . '/Fixtures/InvalidBrick');

            self::fail();
        } catch (Throwable $e) {
            self::assertInstanceOf(PackageContainsNoBrickException::class, $e);
        }
    }

    public function testCannotLoadFromDirWithSeveralBrick()
    {
        $bl = new BrickLoader();
        try {
            $bl->loadFromDir(__DIR__ . '/Fixtures/InvalidBrick2');

            self::fail();
        } catch (Throwable $e) {
            self::assertInstanceOf(PackageContainsSeveralBrickException::class, $e);
        }
    }

    public function testCanLoadFromValidDir()
    {
        $bl = new BrickLoader();
        try {
            $bl->loadFromDir(__DIR__ . '/Fixtures/Brick');
        } catch (Throwable $e) {
            self::fail($e);
        }

        self::assertCount(1, BrickManager::instance()->getBricks());
    }

    public function testCanLoadFromCache()
    {
        $bl = new BrickLoader();
        try {
            $bl->loadFromDir(__DIR__ . '/Fixtures/Brick');
        } catch (Throwable $e) {
            self::fail($e);
        }

        $bricks = BrickManager::instance()->getBricks();
        self::assertCount(1, $bricks);

        BrickManager::flush();

        $bl = new BrickLoader();
        $bl->loadFromCache();

        $actual = BrickManager::instance()->getBricks();
        self::assertCount(1, $actual);
        self::assertEqualsCanonicalizing($bricks, $actual);
    }
}
