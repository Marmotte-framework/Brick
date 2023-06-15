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

namespace Marmotte\Brick\Commands;

use Marmotte\Brick\Bricks\BrickManager;
use Marmotte\Brick\Services\Service;
use Marmotte\Brick\Services\ServiceManager;
use ReflectionClass;

#[Service(autoload: false)]
final class CLI
{
    /**
     * @var array<string, ReflectionClass>
     */
    private readonly array $commands;

    public function __construct(
        private readonly BrickManager   $brick_manager,
        private readonly ServiceManager $service_manager,
    ) {
        $command_classes = $this->brick_manager->getClassMap(
            static fn(ReflectionClass $class): bool => !empty($class->getAttributes(Command::class))
                                                       && $class->implementsInterface(CommandInterface::class)
        );

        $commands = [];
        foreach ($command_classes as $command_class) {
            $command_attr      = $command_class->getAttributes(Command::class)[0];
            $command_attr_inst = $command_attr->newInstance();

            $commands[$command_attr_inst->name] = $command_class;
        }
        $this->commands = $commands;
    }

    public function hasCommand(string $name): bool
    {
        return array_key_exists($name, $this->commands);
    }

    /**
     * @return string[]
     */
    public function getCommandNames(): array
    {
        return array_keys($this->commands);
    }

    /**
     * @return array{
     *     name: string,
     *     description: string,
     *     arguments: array{
     *         name: string,
     *         description: string,
     *         type: string,
     *         required: bool,
     *         repeatable: bool,
     *     }[],
     * }|null
     */
    public function getCommandDetails(string $name): ?array
    {
        if (!$this->hasCommand($name)) {
            return null;
        }

        $command      = $this->commands[$name];
        $command_attr = $command->getAttributes(Command::class)[0]->newInstance();

        $arguments = [];
        foreach ($command->getAttributes(Argument::class) as $attribute) {
            $inst = $attribute->newInstance();

            $arguments[] = [
                'name'        => $inst->name,
                'description' => $inst->description,
                'type'        => $inst->type,
                'required'    => $inst->required,
                'repeatable'  => $inst->repeatable,
            ];
        }

        return [
            'name'        => $name,
            'description' => $command_attr->description,
            'arguments'   => $arguments,
        ];
    }

    public function run(): never
    {
        // TODO
        
        exit(0);
    }
}