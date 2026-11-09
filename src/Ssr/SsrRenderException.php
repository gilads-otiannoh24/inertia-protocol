<?php

declare(strict_types=1);

namespace Inertia\Protocol\Ssr;

use RuntimeException;

class SsrRenderException extends RuntimeException
{
    /**
     * @param string $message
     * @param string|null $type browser-api, component-resolution, render, connection
     * @param string|null $component
     * @param string|null $url
     * @param string|null $hint
     * @param string|null $stack
     */
    public function __construct(
        string $message,
        public ?string $type = null,
        public ?string $component = null,
        public ?string $url = null,
        public ?string $hint = null,
        public ?string $stack = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }
}
