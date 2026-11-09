<?php

declare(strict_types=1);

namespace Inertia\Protocol\Tests\Unit;

use Inertia\Protocol\Inertia;
use Inertia\Protocol\ProtocolEngine;
use Inertia\Protocol\Support\NativeRequest;
use PHPUnit\Framework\TestCase;

class LivePropsTest extends TestCase
{
    private ProtocolEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new ProtocolEngine();
    }

    public function testLivePropEmitsMetadataOnFullVisit(): void
    {
        $request = new NativeRequest(headers: ['x-inertia' => 'true'], url: '/orders/1');

        $decision = $this->engine->evaluate(
            request: $request,
            component: 'Orders/Show',
            props: [
                'order' => Inertia::live(['id' => 1, 'status' => 'shipped'])
                    ->listen('orders.1', ['App\\Events\\OrderUpdated'], type: 'private')
                    ->throttle(5000),
            ]
        );

        $page = $decision->pageObject->toArray();

        $this->assertSame(['id' => 1, 'status' => 'shipped'], $page['props']['order']);
        $this->assertArrayHasKey('liveProps', $page);
        $this->assertSame([
            'order' => [
                'listeners' => [
                    [
                        'channel' => ['name' => 'orders.1', 'type' => 'private'],
                        'events'  => ['App\\Events\\OrderUpdated'],
                    ],
                ],
                'throttle' => 5000,
            ],
        ], $page['liveProps']);
    }

    public function testLivePropResolvedInPartialReloadEmitsMetadata(): void
    {
        $request = new NativeRequest(
            headers: [
                'x-inertia'                   => 'true',
                'x-inertia-partial-component' => 'Orders/Show',
                'x-inertia-partial-data'      => 'order',
                'x-socket-id'                 => '1234.5678',
            ],
            url: '/orders/1'
        );

        $decision = $this->engine->evaluate(
            request: $request,
            component: 'Orders/Show',
            props: [
                'order' => Inertia::live(fn() => ['id' => 1, 'status' => 'delivered'])
                    ->listen('orders.1', 'OrderUpdated'),
                'other' => 'ignored',
            ]
        );

        $page = $decision->pageObject->toArray();

        $this->assertSame(['id' => 1, 'status' => 'delivered'], $page['props']['order']);
        $this->assertArrayNotHasKey('other', $page['props']);
        $this->assertArrayHasKey('liveProps', $page);
        $this->assertSame([
            'order' => [
                'listeners' => [
                    [
                        'channel' => ['name' => 'orders.1', 'type' => 'private'],
                        'events'  => ['OrderUpdated'],
                    ],
                ],
            ],
        ], $page['liveProps']);
    }
}
