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

use Marmotte\Brick\Commands\CLI;
use Marmotte\Brick\Exceptions\CLIException;

final class CLITest extends BrickTestCase
{
    public function testItHasCommands(): void
    {
        try {
            $this->brick_loader->loadFromDir(__DIR__ . '/Fixtures/Brick');
            $service_manager = $this->brick_manager->initialize(__DIR__ . '/Fixtures', __DIR__ . '/Fixtures');
        } catch (\Throwable $e) {
            self::fail($e->getMessage());
        }

        $cli = $service_manager->getService(CLI::class);
        self::assertNotNull($cli);

        self::assertTrue($cli->hasCommand('help'));
    }

    public function testItThrowsWhenBadArgs(): void
    {
        try {
            $this->brick_loader->loadFromDir(__DIR__ . '/Fixtures/Brick');
            $service_manager = $this->brick_manager->initialize(__DIR__ . '/Fixtures', __DIR__ . '/Fixtures');
        } catch (\Throwable $e) {
            self::fail($e->getMessage());
        }

        $cli = $service_manager->getService(CLI::class);
        self::assertNotNull($cli);

        self::expectException(CLIException::class);
        $cli->run(0, []);
    }
}