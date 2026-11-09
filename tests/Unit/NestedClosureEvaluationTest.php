<?php

declare(strict_types=1);

namespace Inertia\Protocol\Tests\Unit;

use Inertia\Protocol\ProtocolEngine;
use Inertia\Protocol\Support\NativeRequest;
use PHPUnit\Framework\TestCase;

class NestedClosureEvaluationTest extends TestCase
{
    private ProtocolEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new ProtocolEngine();
    }

    public function testNestedClosuresInsideArraysAreResolvedRecursively(): void
    {
        $request = new NativeRequest(headers: ['x-inertia' => 'true'], url: '/app');

        $decision = $this->engine->evaluate(
            request: $request,
            component: 'App',
            props: [
                'auth' => [
                    'user' => fn() => [
                        'id'    => 42,
                        'name'  => 'Ian',
                        'roles' => fn() => ['admin', 'maintainer'],
                    ],
                ],
            ]
        );

        $props = $decision->pageObject->toArray()['props'];

        $this->assertSame([
            'user' => [
                'id'    => 42,
                'name'  => 'Ian',
                'roles' => ['admin', 'maintainer'],
            ],
        ], $props['auth']);

        // Verify JSON serialization does not produce empty {} closures
        $json = json_encode($props);
        $this->assertStringContainsString('"name":"Ian"', $json);
        $this->assertStringContainsString('"roles":["admin","maintainer"]', $json);
    }
}
