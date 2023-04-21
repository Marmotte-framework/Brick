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

namespace Marmotte\Brick\Events;

use Marmotte\Brick\Exceptions\EventNotRegisteredException;
use Marmotte\Brick\Services\Service;
use Marmotte\Brick\Services\ServiceManager;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

#[Service(autoload: false)]
final class EventManager
{
    /**
     * @var array<class-string, array<ReflectionMethod>>
     */
    private array $events = [];

    /**
     * @param ReflectionClass[] $events
     */
    public function __construct(
        array                           $events,
        private readonly ServiceManager $service_manager
    ) {
        foreach ($events as $event) {
            $this->events[$event->getName()] = [];
        }
    }

    /**
     * @param class-string     $event
     * @param ReflectionMethod $method
     * @throws EventNotRegisteredException
     */
    public function addListener(string $event, ReflectionMethod $method): void
    {
        if (!array_key_exists($event, $this->events)) {
            throw new EventNotRegisteredException($event);
        }

        $this->events[$event][] = $method;
    }

    /**
     * @template T of object
     * @psalm-param T $event
     * @return T
     */
    public function dispatch(object $event): object
    {
        if (!array_key_exists($event::class, $this->events)) {
            return $event;
        }

        foreach ($this->events[$event::class] as $listener) {
            $service = $this->service_manager->getService($listener->getDeclaringClass()->getName());
            if ($service) {
                try {
                    $listener->invoke($service, $event);
                } catch (ReflectionException) {
                    // Ignore
                }
            }
        }

        return $event;
    }
}
