<?php

declare(strict_types=1);

namespace Inertia\Protocol\Tests\Unit;

use Inertia\Protocol\Inertia;
use Inertia\Protocol\ProtocolEngine;
use Inertia\Protocol\Support\NativeRequest;
use PHPUnit\Framework\TestCase;

class PartialReloadTest extends TestCase
{
    private ProtocolEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new ProtocolEngine();
    }

    public function testPartialReloadIncludesOnlyRequestedPropsAndAlwaysProps(): void
    {
        $request = new NativeRequest(
            headers: [
                'x-inertia' => 'true',
                'x-inertia-partial-component' => 'Events',
                'x-inertia-partial-data'      => 'events',
            ],
            url: '/events/80'
        );

        $decision = $this->engine->evaluate(
            request: $request,
            component: 'Events',
            props: [
                'events'       => [['id' => 80, 'name' => 'Party']],
                'user'         => ['name' => 'Bob'],
                'alwaysProp'   => Inertia::always('site_online'),
                'lazyProp'     => Inertia::lazy(fn() => 'lazy_val'),
            ]
        );

        $page = $decision->pageObject->toArray();

        // 1. Requested 'events' is resolved
        $this->assertSame([['id' => 80, 'name' => 'Party']], $page['props']['events']);

        // 2. Always props are resolved
        $this->assertSame('site_online', $page['props']['alwaysProp']);

        // 3. Unrequested 'user' and 'lazyProp' are omitted
        $this->assertArrayNotHasKey('user', $page['props']);
        $this->assertArrayNotHasKey('lazyProp', $page['props']);
    }

    public function testPartialReloadResolvesDeferredPropsWithRescueSupport(): void
    {
        $request = new NativeRequest(
            headers: [
                'x-inertia' => 'true',
                'x-inertia-partial-component' => 'Users/Index',
                'x-inertia-partial-data'      => 'permissions,failingProp',
            ],
            url: '/users'
        );

        $decision = $this->engine->evaluate(
            request: $request,
            component: 'Users/Index',
            props: [
                'permissions' => Inertia::defer(fn() => ['admin', 'editor']),
                'failingProp' => Inertia::defer(function () {
                    throw new \RuntimeException('Database unreachable');
                }, rescue: true),
            ]
        );

        $page = $decision->pageObject->toArray();

        // Resolved deferred prop
        $this->assertSame(['admin', 'editor'], $page['props']['permissions']);

        // Rescued failing prop is omitted from props and listed under rescuedProps
        $this->assertArrayNotHasKey('failingProp', $page['props']);
        $this->assertSame(['failingProp'], $page['rescuedProps']);
    }

    public function testPartialReloadResetHeaderOmitsMergeMetadata(): void
    {
        $request = new NativeRequest(
            headers: [
                'x-inertia' => 'true',
                'x-inertia-partial-component' => 'Feed/Index',
                'x-inertia-partial-data'      => 'posts',
                'x-inertia-reset'             => 'posts',
            ],
            url: '/feed'
        );

        $decision = $this->engine->evaluate(
            request: $request,
            component: 'Feed/Index',
            props: [
                'posts' => Inertia::merge([['id' => 99, 'title' => 'Reset Item']]),
            ]
        );

        $page = $decision->pageObject->toArray();

        // Prop is resolved
        $this->assertSame([['id' => 99, 'title' => 'Reset Item']], $page['props']['posts']);

        // Because of reset header, mergeProps is omitted so client replaces value instead of merging
        $this->assertArrayNotHasKey('mergeProps', $page);
    }
}
