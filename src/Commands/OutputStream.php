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

final class OutputStream
{
    private string $buffer = '';

    /**
     * @param resource $stream
     */
    public function __construct(
        private readonly mixed $stream,
    ) {
    }

    public function flush(): void
    {
        fprintf($this->stream, '%s', $this->buffer);
        $this->buffer = '';
    }

    public function write(string $str): self
    {
        $this->buffer .= $str;

        return $this;
    }

    public function writeln(string $str = ''): self
    {
        return $this->write($str . "\n");
    }

    public function color(Color $color): self
    {
        return $this->write($color->value);
    }

    public function resetColor(): self
    {
        return $this->color(Color::RESET);
    }

    public function bold(): self
    {
        return $this->color(Color::BOLD);
    }

    public function underline(): self
    {
        return $this->color(Color::UNDERLINE);
    }

    public function blink(): self
    {
        return $this->color(Color::BLINK);
    }

    public function inverse(): self
    {
        return $this->color(Color::INVERSE);
    }
}