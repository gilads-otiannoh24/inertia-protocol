<?php

declare(strict_types=1);

namespace Inertia\Protocol\Ssr;

class SsrResponse
{
    /**
     * @param list<string> $head HTML elements for the <head> section
     * @param string $body Rendered HTML body markup including <script data-page="app"> and <div data-server-rendered="true">
     */
    public function __construct(
        public array $head,
        public string $body
    ) {
    }

    /**
     * Return head tags concatenated as a single string.
     */
    public function head(): string
    {
        return implode("\n", $this->head);
    }

    /**
     * Return the rendered body markup.
     */
    public function body(): string
    {
        return $this->body;
    }
}
