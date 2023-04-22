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
use Marmotte\Brick\Events\EventManager;
use Marmotte\Brick\Exceptions\EventNotRegisteredException;
use Marmotte\Brick\Fixtures\Brick\AnEvent;
use Marmotte\Brick\Fixtures\Brick\AService;
use Marmotte\Brick\Fixtures\Brick\AServiceConfig;
use Marmotte\Brick\Services\ServiceManager;

class BrickManagerTest extends BrickTestCase
{
    public function testCanLoadBrick(): void
    {
        try {
            $this->brick_loader->loadFromDir(__DIR__ . '/Fixtures/Brick');
            $service_manager = $this->brick_manager->initialize(__DIR__ . '/Fixtures', __DIR__ . '/Fixtures');
        } catch (\Throwable $e) {
            self::fail($e->getMessage());
        }

        self::assertCount(1, $this->brick_manager->getBricks());

        self::assertTrue($service_manager->hasService(AService::class));
        self::assertTrue($service_manager->hasService(ServiceManager::class));
        self::assertTrue($service_manager->hasService(BrickManager::class));

        $service = $service_manager->getService(AService::class);
        self::assertInstanceOf(AService::class, $service);
        $config = $service->config;
        self::assertInstanceOf(AServiceConfig::class, $config);
        self::assertEquals('world!', $config->hello);
        self::assertEquals(__DIR__ . '/Fixtures', $config->project_root);

        $event_manager = $service_manager->getService(EventManager::class);
        self::assertNotNull($event_manager);
        $event = $event_manager->dispatch(new AnEvent(-2));
        self::assertEquals(42, $event->value);

        self::assertEquals(2, $service::$counter);
    }
}
