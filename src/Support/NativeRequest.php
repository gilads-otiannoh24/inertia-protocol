<?php

declare(strict_types=1);

namespace Inertia\Protocol\Support;

use Inertia\Protocol\Contracts\RequestInterface;

class NativeRequest implements RequestInterface
{
    /**
     * @param array<string, string> $headers Normalized lowercase header map
     * @param string $method
     * @param string $url
     * @param array<string, mixed> $queryParams
     */
    public function __construct(
        protected array $headers = [],
        protected string $method = 'GET',
        protected string $url = '/',
        protected array $queryParams = []
    ) {
        $normalized = [];
        foreach ($headers as $k => $v) {
            $normalized[strtolower((string) $k)] = (string) $v;
        }
        $this->headers = $normalized;
    }

    public static function fromGlobals(): self
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', substr($key, 5));
                $headers[strtolower($name)] = (string) $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'], true)) {
                $name = str_replace('_', '-', $key);
                $headers[strtolower($name)] = (string) $value;
            }
        }

        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $url = $_SERVER['REQUEST_URI'] ?? '/';
        $queryParams = $_GET ?? [];

        return new self($headers, $method, $url, $queryParams);
    }

    public function getHeaderLine(string $name): string
    {
        return $this->headers[strtolower($name)] ?? '';
    }

    public function hasHeader(string $name): bool
    {
        return !empty($this->headers[strtolower($name)]);
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getQueryParam(string $name, mixed $default = null): mixed
    {
        return $this->queryParams[$name] ?? $default;
    }
}
