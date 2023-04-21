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

namespace Marmotte\Brick\Cache;

use Marmotte\Brick\Mode;
use Marmotte\Brick\Services\Service;

#[Service(autoload: false)]
final class CacheManager
{
    private static ?self $instance = null;

    private function __construct(
        private readonly string $cache_dir,
        private readonly Mode   $mode,
    ) {
        if (!file_exists($this->cache_dir)) {
            mkdir($this->cache_dir);
        }
    }

    public static function instance(string $cache_dir = '', Mode $mode = Mode::PROD): self
    {
        if (self::$instance === null) {
            self::$instance = new self($cache_dir, $mode);
        }

        return self::$instance;
    }

    public function save(string $path, string $name, mixed $object): void
    {
        if ($this->mode == Mode::PROD) { // Can save only in production
            $content = serialize($object);

            file_put_contents($this->cache_dir . '/' . $this->getFileName($path, $name), $content);
        }
    }

    /**
     * @param string $path
     * @param string $name
     * @return mixed|null
     */
    public function load(string $path, string $name): mixed
    {
        $filename = $this->cache_dir . '/' . $this->getFileName($path, $name);
        if ($this->mode == Mode::PROD && file_exists($filename)) {
            $content = file_get_contents($filename);

            if ($content) {
                return unserialize($content);
            }
        }

        return null;
    }

    public function exists(string $path, string $name): bool
    {
        $filename = $this->cache_dir . '/' . $this->getFileName($path, $name);

        return $this->mode == Mode::PROD && file_exists($filename);
    }

    // _.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-.

    private function getFileName(string $path, string $name): string
    {
        return hash('sha512', $path . $name);
    }
}
