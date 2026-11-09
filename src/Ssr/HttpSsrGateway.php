<?php

declare(strict_types=1);

namespace Inertia\Protocol\Ssr;

use Inertia\Protocol\PageObject;
use Throwable;

class HttpSsrGateway implements SsrGatewayInterface
{
    /**
     * @param string $url Base URL of the Inertia Node SSR server (or Vite dev endpoint)
     * @param float $timeout Timeout in seconds
     * @param bool $throwOnError Whether to throw SsrRenderException on failed renders
     */
    public function __construct(
        protected string $url = 'http://127.0.0.1:13714',
        protected float $timeout = 1.0,
        protected bool $throwOnError = false
    ) {
        $this->url = rtrim($url, '/');
    }

    public function render(PageObject $page): ?SsrResponse
    {
        $payload = $page->toJson();
        $endpoint = str_ends_with($this->url, '/__inertia_ssr') ? $this->url : "{$this->url}/render";

        $context = stream_context_create([
            'http' => [
                'method'        => 'POST',
                'header'        => "Content-Type: application/json\r\nAccept: application/json\r\n",
                'content'       => $payload,
                'timeout'       => $this->timeout,
                'ignore_errors' => true,
            ],
        ]);

        try {
            $response = @file_get_contents($endpoint, false, $context);
            if ($response === false) {
                if ($this->throwOnError) {
                    throw new SsrRenderException("Failed to connect to Inertia SSR server at {$endpoint}", type: 'connection');
                }
                return null;
            }

            $statusLine = $http_response_header[0] ?? '';
            preg_match('{HTTP\/\S*\s(\d{3})}', $statusLine, $match);
            $statusCode = isset($match[1]) ? (int) $match[1] : 200;

            $data = json_decode($response, true);

            if ($statusCode !== 200 || !is_array($data) || !isset($data['body'])) {
                if ($this->throwOnError) {
                    $errorMsg = $data['error'] ?? 'Inertia SSR server error';
                    throw new SsrRenderException(
                        message: $errorMsg,
                        type: $data['type'] ?? 'render',
                        component: $data['component'] ?? $page->component,
                        url: $data['url'] ?? $page->url,
                        hint: $data['hint'] ?? null,
                        stack: $data['stack'] ?? null
                    );
                }
                return null;
            }

            return new SsrResponse(
                head: (array) ($data['head'] ?? []),
                body: (string) $data['body']
            );
        } catch (Throwable $e) {
            if ($this->throwOnError) {
                if ($e instanceof SsrRenderException) {
                    throw $e;
                }
                throw new SsrRenderException(
                    message: $e->getMessage(),
                    type: 'connection',
                    component: $page->component,
                    url: $page->url,
                    previous: $e
                );
            }
            return null;
        }
    }

    public function health(): bool
    {
        $context = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'timeout' => $this->timeout,
            ],
        ]);

        try {
            $response = @file_get_contents("{$this->url}/health", false, $context);
            if ($response === false) {
                return false;
            }
            $data = json_decode($response, true);
            return is_array($data) && ($data['status'] ?? '') === 'OK';
        } catch (Throwable) {
            return false;
        }
    }

    public function shutdown(): bool
    {
        $context = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'timeout' => $this->timeout,
            ],
        ]);

        try {
            $response = @file_get_contents("{$this->url}/shutdown", false, $context);
            return $response !== false;
        } catch (Throwable) {
            return false;
        }
    }
}
