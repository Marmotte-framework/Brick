<?php

namespace Marmotte\Brick\Config;

abstract class ServiceConfig
{
    /**
     * Returns a new instance of Config from $array
     */
    public static abstract function fromArray(array $array): self;

    /**
     * Returns the default configuration as an array
     */
    public static abstract function defaultArray(): array;

    /**
     * Absolute path to project root
     * @psalm-suppress PropertyNotSetInConstructor
     */
    public string $project_root;
}
