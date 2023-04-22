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
use PHPUnit\Framework\TestCase;

abstract class BrickTestCase extends TestCase
{
    /** @psalm-suppress PropertyNotSetInConstructor */
    protected CacheManager $cache_manager;
    /** @psalm-suppress PropertyNotSetInConstructor */
    protected BrickLoader $brick_loader;
    /** @psalm-suppress PropertyNotSetInConstructor */
    protected BrickManager $brick_manager;

    private static function rmDir(string $dir): void
    {
        $files = array_diff(scandir($dir), ['.', '..']);

        /** @var string $file */
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? self::rmDir("$dir/$file") : unlink("$dir/$file");
        }

        rmdir($dir);
    }

    protected function setUp(): void
    {
        $this->cache_manager = new CacheManager(__DIR__ . '/cache');
        $this->brick_manager = new BrickManager();
        $this->brick_loader  = new BrickLoader(
            $this->brick_manager,
            $this->cache_manager
        );
    }

    protected function tearDown(): void
    {
        self::rmDir(__DIR__ . '/cache');
        mkdir(__DIR__ . '/cache');
    }
}
