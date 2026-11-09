<?php

declare(strict_types=1);

namespace Inertia\Protocol\Tests\Unit;

use Inertia\Protocol\PageObject;
use Inertia\Protocol\Ssr\HttpSsrGateway;
use Inertia\Protocol\Ssr\SsrRenderException;
use Inertia\Protocol\Ssr\SsrResponse;
use PHPUnit\Framework\TestCase;

class SsrGatewayTest extends TestCase
{
    public function testSsrResponseData(): void
    {
        $response = new SsrResponse(
            head: ['<title>My App</title>', '<meta name="description" content="test">'],
            body: '<script data-page="app" type="application/json">{}</script><div data-server-rendered="true" id="app">Hello</div>'
        );

        $this->assertSame("<title>My App</title>\n<meta name=\"description\" content=\"test\">", $response->head());
        $this->assertStringContainsString('data-server-rendered="true"', $response->body());
    }

    public function testGatewayFallsBackGracefullyWhenServerUnreachableAndNotThrowing(): void
    {
        // Use an invalid port where SSR is not running
        $gateway = new HttpSsrGateway(url: 'http://127.0.0.1:59999', timeout: 0.1, throwOnError: false);
        $page = new PageObject('Home', [], '/');

        $result = $gateway->render($page);
        $this->assertNull($result);
        $this->assertFalse($gateway->health());
    }

    public function testGatewayThrowsSsrRenderExceptionWhenConfigured(): void
    {
        $gateway = new HttpSsrGateway(url: 'http://127.0.0.1:59999', timeout: 0.1, throwOnError: true);
        $page = new PageObject('Home', [], '/');

        $this->expectException(SsrRenderException::class);
        $gateway->render($page);
    }
}
