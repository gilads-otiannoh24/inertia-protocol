<?php

declare(strict_types=1);

namespace Inertia\Protocol\Tests\Unit;

use Inertia\Protocol\ProtocolEngine;
use Inertia\Protocol\Support\NativeRequest;
use PHPUnit\Framework\TestCase;

class DotNotationPartialReloadTest extends TestCase
{
    private ProtocolEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new ProtocolEngine();
    }

    public function testDotNotationInPartialDataFiltersPlainArray(): void
    {
        $request = new NativeRequest(
            headers: [
                'x-inertia'                   => 'true',
                'x-inertia-partial-component' => 'Users/Show',
                'x-inertia-partial-data'      => 'user.address.city',
            ],
            url: '/users/1'
        );

        $decision = $this->engine->evaluate(
            request: $request,
            component: 'Users/Show',
            props: [
                'user' => [
                    'name'    => 'Jonathan',
                    'email'   => 'jonathan@example.com',
                    'address' => [
                        'city'    => 'Nairobi',
                        'country' => 'Kenya',
                    ],
                ],
                'otherProp' => 'ignored',
            ]
        );

        $props = $decision->pageObject->toArray()['props'];

        $this->assertArrayNotHasKey('otherProp', $props);
        $this->assertArrayHasKey('user', $props);
        $this->assertArrayNotHasKey('name', $props['user']);
        $this->assertArrayNotHasKey('email', $props['user']);
        $this->assertSame(['city' => 'Nairobi'], $props['user']['address']);
    }

    public function testDotNotationInPartialExceptFiltersNestedArray(): void
    {
        $request = new NativeRequest(
            headers: [
                'x-inertia'                   => 'true',
                'x-inertia-partial-component' => 'Auth/Profile',
                'x-inertia-partial-data'      => 'auth',
                'x-inertia-partial-except'    => 'auth.token',
            ],
            url: '/profile'
        );

        $decision = $this->engine->evaluate(
            request: $request,
            component: 'Auth/Profile',
            props: [
                'auth' => [
                    'user'  => ['id' => 1, 'name' => 'Ian'],
                    'token' => 'secret_12345',
                ],
            ]
        );

        $props = $decision->pageObject->toArray()['props'];

        $this->assertArrayHasKey('auth', $props);
        $this->assertSame(['id' => 1, 'name' => 'Ian'], $props['auth']['user']);
        $this->assertArrayNotHasKey('token', $props['auth']);
    }

    public function testDotNotationOnClosureResolvesClosureInFullPerSpec(): void
    {
        // Per spec: "The Laravel adapter applies nested paths only inside plain arrays, and returns a prop that a closure resolves in full."
        $request = new NativeRequest(
            headers: [
                'x-inertia'                   => 'true',
                'x-inertia-partial-component' => 'Users/Show',
                'x-inertia-partial-data'      => 'user.name',
            ],
            url: '/users/1'
        );

        $decision = $this->engine->evaluate(
            request: $request,
            component: 'Users/Show',
            props: [
                'user' => fn() => [
                    'name'  => 'Jonathan',
                    'email' => 'jonathan@example.com',
                ],
            ]
        );

        $props = $decision->pageObject->toArray()['props'];

        $this->assertSame([
            'name'  => 'Jonathan',
            'email' => 'jonathan@example.com',
        ], $props['user']);
    }
}
