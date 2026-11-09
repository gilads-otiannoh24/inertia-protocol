<?php

declare(strict_types=1);

namespace Inertia\Protocol\Tests\Unit;

use Inertia\Protocol\Inertia;
use Inertia\Protocol\Support\NativeRequest;
use PHPUnit\Framework\TestCase;

class SharedPropsLazyEvaluationTest extends TestCase
{
    protected function tearDown(): void
    {
        Inertia::flushShared();
        parent::tearDown();
    }

    public function testSharedPropClosureIsNotExecutedWhenUnrequestedInPartialReload(): void
    {
        $executed = false;

        Inertia::share('expensiveShared', function () use (&$executed) {
            $executed = true;
            return 'heavy_computation';
        });

        $response = Inertia::render('Dashboard', [
            'fastProp' => 'fast',
        ]);

        $request = new NativeRequest(
            headers: [
                'x-inertia'                   => 'true',
                'x-inertia-partial-component' => 'Dashboard',
                'x-inertia-partial-data'      => 'fastProp',
            ],
            url: '/dashboard'
        );

        $decision = $response->toDecision($request);
        $page = $decision->pageObject->toArray();

        // 1. Partial reload requested 'fastProp' only
        $this->assertSame('fast', $page['props']['fastProp']);
        $this->assertArrayNotHasKey('expensiveShared', $page['props']);

        // 2. The shared closure MUST NOT have been executed
        $this->assertFalse($executed, 'Shared closure was executed prematurely during partial reload!');
    }

    public function testSharedPropClosureIsExecutedWhenRequestedOrOnFullVisit(): void
    {
        $executed = false;

        Inertia::share('sharedUser', function () use (&$executed) {
            $executed = true;
            return ['id' => 10, 'name' => 'Ian'];
        });

        $response = Inertia::render('Profile');

        // Full visit
        $request = new NativeRequest(headers: ['x-inertia' => 'true'], url: '/profile');
        $decision = $response->toDecision($request);
        $page = $decision->pageObject->toArray();

        $this->assertTrue($executed);
        $this->assertSame(['id' => 10, 'name' => 'Ian'], $page['props']['sharedUser']);
        $this->assertSame(['sharedUser'], $page['sharedProps']);
    }
}
