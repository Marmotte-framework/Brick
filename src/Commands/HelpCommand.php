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

#[Command("help", "Display this help message")]
#[Argument("command", "Display help for this command")]
final class HelpCommand implements CommandInterface
{
    public function __construct(
        private readonly CLI $cli,
    ) {
    }

    public function run(InputStream $input, OutputStream $output): bool
    {
        if ($input->has("command")) {
            /** @var string $command */
            $command = $input->get("command");
            $this->displayLongHelpForCommand($output, $command);

            return true;
        }

        $output->writeln("Available commands:");
        foreach ($this->cli->getCommandNames() as $command) {
            $this->displayShortHelpForCommand($output, $command);
        }

        return true;
    }

    private function displayShortHelpForCommand(OutputStream $output, string $command): void
    {
        $details = $this->cli->getCommandDetails($command);

        $output->writeln(sprintf("\t%s\t\t%s", $command, $details ? $details['description'] : ''));
    }

    private function displayLongHelpForCommand(OutputStream $output, string $command): void
    {
        $details = $this->cli->getCommandDetails($command);
        if (!$details) {
            $output->color(Color::RED)
                   ->writeln(sprintf("Command %s not found", $command));
            return;
        }

        $output->writeln($command)
               ->write("\t")
               ->writeln($details['description']);

        foreach ($details['arguments'] as $argument) {
            if ($argument['required']) {
                $output->writeln(
                    sprintf(
                        "%s -> %s\t\t%s",
                        $argument['name'],
                        $argument['type'],
                        $argument['description']
                    )
                );
            } else {
                $output->writeln(
                    sprintf(
                        "[%s] -> %s\t\t%s",
                        $argument['name'],
                        $argument['type'],
                        $argument['description']
                    )
                );
            }
        }
    }
}