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

namespace Marmotte\Brick\Bricks;

use Marmotte\Brick\Cache\CacheManager;
use Marmotte\Brick\Commands\CLI;
use Marmotte\Brick\Events\Event;
use Marmotte\Brick\Events\EventListener;
use Marmotte\Brick\Events\EventManager;
use Marmotte\Brick\Exceptions\ClassIsNotServiceException;
use Marmotte\Brick\Exceptions\EventNotRegisteredException;
use Marmotte\Brick\Exceptions\ServiceAlreadyLoadedException;
use Marmotte\Brick\Exceptions\ServicesAreCycleDependentException;
use Marmotte\Brick\Services\Service;
use Marmotte\Brick\Services\ServiceManager;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;

#[Service(autoload: false)]
final class BrickManager
{
    /** @psalm-suppress PropertyNotSetInConstructor */
    private CacheManager $cache_manager;

    public function setCacheManager(CacheManager $cache_manager): void
    {
        $this->cache_manager = $cache_manager;
    }

    /**
     * @throws ServicesAreCycleDependentException
     * @throws ClassIsNotServiceException
     * @throws ServiceAlreadyLoadedException
     * @throws EventNotRegisteredException
     */
    public function initialize(string $project_root, string $config_path): ServiceManager
    {
        // Get Services
        $services        = $this->getClassMap(
            static fn(ReflectionClass $class) => !empty($class->getAttributes(Service::class))
        );
        $service_manager = new ServiceManager($project_root, $config_path);
        $service_manager->addService($this);

        $service_manager->addService($this->cache_manager);

        // Add CLI
        $service_manager->addService(new CLI($this, $service_manager));

        // Get Events
        $events        = $this->getClassMap(
            static fn(ReflectionClass $class) => !empty($class->getAttributes(Event::class))
        );
        $event_manager = new EventManager($events, $service_manager);
        $service_manager->addService($event_manager);

        // Load services
        $service_manager->loadServices($services);

        // Get EventListeners
        foreach ($services as $service) {
            foreach ($service->getMethods() as $method) {
                $attr = $method->getAttributes(EventListener::class);
                if (empty($attr)) {
                    continue; // Method must have EventListener attribute
                }

                if ($method->getNumberOfParameters() != 1) {
                    continue; // Method must have only 1 parameter
                }

                $param      = $method->getParameters()[0];
                $param_type = $param->getType();
                if (!$param_type instanceof ReflectionNamedType) {
                    continue; // The param type must be explicit
                }
                /** @var class-string */
                $type_name = $param_type->getName();

                // We can add the method to listeners
                $event_manager->addListener($type_name, $method);
            }
        }

        // Call init on Bricks
        $leftovers = $this->bricks;
        while (!empty($leftovers)) {
            $next   = [];
            $change = false;

            foreach ($leftovers as $brick_presenter) {
                $brick = $brick_presenter->brick;
                try {
                    $init = $brick->getMethod('init');

                    $args = [];
                    foreach ($init->getParameters() as $param) {
                        $type = $param->getType();
                        if (!$type instanceof ReflectionNamedType) {
                            break;
                        }

                        $type_name = $type->getName();
                        if (class_exists($type_name) && $service_manager->hasService($type_name)) {
                            $args[] = $service_manager->getService($type_name);
                        } else {
                            break;
                        }
                    }
                    if (count($args) != $init->getNumberOfParameters()) {
                        $next[] = $brick_presenter;
                        continue;
                    }

                    $change = true;
                    $init->invoke($brick->newInstance(), ...$args);
                } catch (ReflectionException) {
                    // Method init not found, ignore it.
                    // It's not mandatory to have an init method
                }
            }

            $leftovers = $next;
            if (!$change) {
                break;
            }
        }
        if (!empty($leftovers)) {
            throw new ServicesAreCycleDependentException([]);
        }

        return $service_manager;
    }

    // _.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-.

    /**
     * @var BrickPresenter[]
     */
    private array $bricks = [];

    public function addBrick(BrickPresenter $brick): void
    {
        $this->bricks[] = $brick;
    }

    public function addBricks(BrickPresenter ...$bricks): void
    {
        foreach ($bricks as $brick) {
            $this->addBrick($brick);
        }
    }

    public function getBrick(string $package): ?BrickPresenter
    {
        foreach ($this->bricks as $brick) {
            if ($brick->package === $package) {
                return $brick;
            }
        }

        return null;
    }

    /**
     * @return BrickPresenter[]
     */
    public function getBricks(): array
    {
        return $this->bricks;
    }

    /**
     * @psalm-param ?callable(ReflectionClass): bool $filter
     * @return ReflectionClass[]
     */
    public function getClassMap(?callable $filter = null): array
    {
        $class_map = [];
        foreach ($this->bricks as $brick) {
            $class_map = array_merge($class_map, $brick->getClassMap($filter));
        }

        return $class_map;
    }
}
