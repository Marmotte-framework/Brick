<?php

namespace Marmotte\Brick\Fixtures\Brick;

use Marmotte\Brick\Config\ServiceConfig;

final class AServiceConfig extends ServiceConfig
{
    public function __construct(
        public readonly string $hello,
    ) {}

    public static function fromArray(array $array): ServiceConfig
    {
        return new self($array['hello']);
    }

    public static function defaultArray(): array
    {
        return [
            'hello' => 'world!'
        ];
    }
}
