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

use Marmotte\Brick\Brick;
use ReflectionClass;
use ReflectionException;

final class BrickPresenter
{
    /**
     * @param string                 $package
     * @param ReflectionClass<Brick> $brick
     * @param ReflectionClass[]      $class_map
     */
    public function __construct(
        public readonly string          $package,
        public readonly ReflectionClass $brick,
        public readonly array           $class_map,
    ) {
    }

    /**
     * @psalm-param ?callable(ReflectionClass): bool $filter
     * @return ReflectionClass[]
     */
    public function getClassMap(?callable $filter = null): array
    {
        return array_filter($this->class_map, $filter);
    }

    public function __serialize(): array
    {
        return [
            'package'   => $this->package,
            'brick'     => $this->brick->getName(),
            'class_map' => array_map(
                static fn(ReflectionClass $class) => $class->getName(),
                $this->class_map
            ),
        ];
    }

    /**
     * @throws ReflectionException
     */
    public function __unserialize(array $data): void
    {
        /** @var string */
        $this->package = $data['package'];

        /** @var class-string */
        $brick_name = $data['brick'];
        /** @var ReflectionClass<Brick> */
        $this->brick = new ReflectionClass($brick_name);

        /** @var class-string[] */
        $class_map_names = $data['class_map'];
        $this->class_map = array_map(
            static fn(string $class) => new ReflectionClass($class),
            $class_map_names
        );
    }
}
