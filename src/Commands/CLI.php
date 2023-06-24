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
use Marmotte\Brick\Exceptions\CLIException;
use Marmotte\Brick\Services\Service;
use Marmotte\Brick\Services\ServiceManager;
use ReflectionClass;
use ReflectionException;

#[Service(autoload: false)]
final class CLI
{
    /**
     * @var array<string, ReflectionClass<CommandInterface>>
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

        /** @var array<string, ReflectionClass<CommandInterface>> $commands */
        $commands = [
            'help' => new ReflectionClass(HelpCommand::class),
        ];
        foreach ($command_classes as $command_class) {
            $command_attr      = $command_class->getAttributes(Command::class)[0];
            $command_attr_inst = $command_attr->newInstance();

            /** @var ReflectionClass<CommandInterface> $command_class */
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
     *         required: bool,
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
                'required'    => $inst->required,
            ];
        }

        return [
            'name'        => $name,
            'description' => $command_attr->description,
            'arguments'   => $arguments,
        ];
    }

    /**
     * @param string[] $argv
     * @throws CLIException
     */
    public function run(int $argc, array $argv): never
    {
        if ($argc <= 0 || empty($argv) || $argc !== count($argv)) {
            throw new CLIException("argc or argv are malformed");
        }

        // Remove script name to args
        array_shift($argv);

        $command_name = array_shift($argv) ?? "help";

        $command_class = array_key_exists($command_name, $this->commands) ? $this->commands[$command_name] : null;
        if (!$command_class) {
            if ($command_name === "help") {
                throw new CLIException("Command help not found. Please contact the code owner");
            } else {
                $this->run(2, ['_script_', 'help']); // Run help command
            }
        }

        $argument_attrs = $command_class->getAttributes(Argument::class);
        $args           = [];
        foreach ($argument_attrs as $argument_attr) {
            $argument = $argument_attr->newInstance();

            $current_arg = array_shift($argv);
            if ($current_arg === null) {
                if ($argument->required) {
                    throw new CLIException(sprintf("Argument %s is required but not provided, see help for more details", $argument->name));
                }

                break;
            }

            if (array_key_exists($argument->name, $args)) {
                throw new CLIException(sprintf("Argument %s is already provided", $argument->name));
            } else {
                $args[$argument->name] = $current_arg;
            }
        }

        try {
            $command = $this->instantiateCommand($command_class);
        } catch (ReflectionException) {
            throw new CLIException(sprintf("Failed to instantiate command %s", $command_name));
        }

        $output = new OutputStream(STDOUT);
        $result = $command->run(new InputStream($args), $output);
        $output->resetColor()->writeln();
        exit($result ? 0 : 1);
    }

    /**
     * @template T of CommandInterface
     * @param ReflectionClass<T> $command_class
     * @return T
     * @throws ReflectionException|CLIException
     */
    private function instantiateCommand(ReflectionClass $command_class)
    {
        $constructor = $command_class->getConstructor();
        if (!$constructor) {
            return $command_class->newInstance();
        }

        $args = [];
        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();
            if (!($type instanceof \ReflectionNamedType)) {
                throw new CLIException(sprintf(
                                           "Argument %d of %s command constructor is invalid",
                                           $parameter->getPosition(),
                                           $command_class->getName()
                                       ));
            }

            /** @var class-string */
            $type_name = $type->getName();
            if (class_exists($type_name) && $this->service_manager->hasService($type_name)) {
                $args[] = $this->service_manager->getService($type_name);
            } else {
                throw new CLIException(sprintf(
                                           "Argument %d of %s command constructor is not a Service",
                                           $parameter->getPosition(),
                                           $command_class->getName()
                                       ));
            }
        }

        return $command_class->newInstance(...$args);
    }
}