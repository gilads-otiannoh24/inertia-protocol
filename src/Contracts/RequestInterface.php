<?php

declare(strict_types=1);

namespace Inertia\Protocol\Contracts;

interface RequestInterface
{
    /**
     * Get a single header line by name (case-insensitive).
     */
    public function getHeaderLine(string $name): string;

    /**
     * Check if a header exists.
     */
    public function hasHeader(string $name): bool;

    /**
     * Get the HTTP request method (e.g. GET, POST, PUT, DELETE).
     */
    public function getMethod(): string;

    /**
     * Get the request URL / path with query and fragment if available.
     */
    public function getUrl(): string;

    /**
     * Get a query parameter value.
     */
    public function getQueryParam(string $name, mixed $default = null): mixed;
}
