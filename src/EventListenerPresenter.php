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

namespace Marmot\Brick;

use ReflectionClass;
use ReflectionMethod;

final class EventListenerPresenter
{
    /**
     * @param ReflectionClass  $service
     * @param ReflectionMethod $method
     * @param ReflectionClass  $event
     */
    public function __construct(
        private readonly ReflectionClass  $service,
        private readonly ReflectionMethod $method,
        private readonly ReflectionClass  $event,
    ) {
    }

    /**
     * @return ReflectionClass
     */
    public function getService(): ReflectionClass
    {
        return $this->service;
    }

    /**
     * @return ReflectionMethod
     */
    public function getMethod(): ReflectionMethod
    {
        return $this->method;
    }

    /**
     * @return ReflectionClass
     */
    public function getEvent(): ReflectionClass
    {
        return $this->event;
    }

    public function __serialize(): array
    {
        return [
            'service' => serialize($this->service),
            'method'  => serialize($this->method),
            'event'   => serialize($this->event)
        ];
    }

    /**
     * @param array<string, string> $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        /** @var ReflectionClass */
        $this->service = unserialize($data['service']);
        /** @var ReflectionMethod */
        $this->method = unserialize($data['method']);
        /** @var ReflectionClass */
        $this->event = unserialize($data['event']);
    }
}
