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

namespace Marmotte\Brick\Services;

use Marmotte\Brick\Config\ServiceConfig;
use Marmotte\Brick\Exceptions\ClassIsNotServiceException;
use Marmotte\Brick\Exceptions\ServiceAlreadyLoadedException;
use Marmotte\Brick\Exceptions\ServiceHasNoConstructor;
use Marmotte\Brick\Exceptions\ServicesAreCycleDependentException;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use Symfony\Component\Yaml\Yaml;
use Throwable;

#[Service(autoload: false)]
final class ServiceManager
{
    private static self $instance;

    /**
     * @throws ServiceAlreadyLoadedException
     */
    public function __construct(
        private readonly string $project_root,
        private readonly string $config_path
    ) {
        self::$instance = $this;

        $this->addService($this);
    }

    public static function instance(): self
    {
        return self::$instance;
    }

    // _.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-.

    /**
     * @var class-string-map<T, T>
     */
    private array $services = [];

    /**
     * @param ReflectionClass[] $services
     * @throws ClassIsNotServiceException
     * @throws ServiceAlreadyLoadedException
     * @throws ServiceHasNoConstructor
     * @throws ServicesAreCycleDependentException
     */
    public function loadServices(array $services): void
    {
        /** @var ReflectionClass[] */
        $leftovers = $services;
        while (!empty($leftovers)) {
            $modified = false;

            /** @var ReflectionClass[] */
            $to_loads  = $leftovers;
            $leftovers = [];
            foreach ($to_loads as $service) {
                if ($this->loadService($service)) {
                    $modified = true;
                } else {
                    $leftovers[] = $service;
                }
            }

            if (!$modified) {
                break;
            }
        }

        if (!empty($leftovers)) {
            throw new ServicesAreCycleDependentException($leftovers);
        }
    }

    /**
     * @throws ClassIsNotServiceException
     * @throws ServiceAlreadyLoadedException
     * @throws ServiceHasNoConstructor
     */
    private function loadService(ReflectionClass $service): bool
    {
        $attrs = $service->getAttributes(Service::class);
        if (empty($attrs)) {
            throw new ClassIsNotServiceException($service);
        }

        $service_attr = $attrs[0]; // Only first attribute is used
        $service_inst = $service_attr->newInstance();

        if (!$service_inst->autoload) {
            // Service will be loaded later by user, so ignore it
            return true;
        }

        // Config file
        if ($service_inst->config_filename !== null) {
            $config_file = $this->config_path . '/' . $service_inst->config_filename;

            if (file_exists($config_file)) {
                /** @var array */
                $config = Yaml::parseFile($config_file);
            }
        }

        $constructor = $service->getConstructor();
        if ($constructor === null) {
            throw new ServiceHasNoConstructor($service);
        }
        $args = [];
        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();
            if (!$type instanceof ReflectionNamedType) {
                break;
            }

            /** @var class-string */
            $type_name = $type->getName();
            if (class_exists($type_name) && $this->hasService($type_name)) {
                $args[] = $this->getService($type_name);
            } else if ($c_config = $this->loadConfig($type_name, $config ?? null)) {
                $args[] = $c_config;
            } else {
                break;
            }
        }
        if (count($args) != $constructor->getNumberOfParameters()) {
            // Some of dependent Services are not yet loaded
            return false;
        }

        try {
            $instance = $service->newInstance(...$args);
            $this->addService($instance);
        } catch (ReflectionException) {
            return false;
        }

        return true;
    }

    /**
     * @param class-string $config_name
     */
    private function loadConfig(string $config_name, ?array $config): false|ServiceConfig
    {
        try {
            $reflection = new ReflectionClass($config_name);

            $parent = $reflection->getParentClass();
            if (!($parent && $parent->getName() === ServiceConfig::class)) {
                return false;
            }

            $method = $reflection->getMethod('fromArray');
            $object = $method->invoke(null, $config);

            if (!$object instanceof ServiceConfig) {
                return false;
            }

            $object->project_root = $this->project_root;

            return $object;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @template T of object
     * @psalm-param T $service
     * @throws ServiceAlreadyLoadedException
     */
    public function addService(object $service): self
    {
        $key = $service::class;

        if (array_key_exists($key, $this->services)) {
            throw new ServiceAlreadyLoadedException($key);
        }

        $this->services[$key] = $service;

        return $this;
    }

    /**
     * @param class-string $service
     */
    public function hasService(string $service): bool
    {
        return array_key_exists($service, $this->services);
    }

    /**
     * @template T as object
     * @psalm-param class-string<T> $service
     * @psalm-return T|null
     */
    public function getService(string $service): ?object
    {
        if ($this->hasService($service)) {
            return $this->services[$service];
        }

        return null;
    }
}
