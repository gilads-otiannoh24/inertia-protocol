<?php

declare(strict_types=1);

namespace Inertia\Protocol\Tests\Unit;

use Inertia\Protocol\Inertia;
use Inertia\Protocol\ProtocolEngine;
use Inertia\Protocol\Support\NativeRequest;
use PHPUnit\Framework\TestCase;

class PropEvaluationTest extends TestCase
{
    private ProtocolEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new ProtocolEngine();
    }

    public function testFullVisitEvaluatesRegularAlwaysAndMergeableProps(): void
    {
        $request = new NativeRequest(
            headers: ['x-inertia' => 'true'],
            method: 'GET',
            url: '/feed'
        );

        $decision = $this->engine->evaluate(
            request: $request,
            component: 'Feed/Index',
            props: [
                'user'          => fn() => ['name' => 'Alice'],
                'alwaysInfo'    => Inertia::always('site_online'),
                'lazyProp'      => Inertia::lazy(fn() => 'expensive_data'),
                'deferredComments' => Inertia::defer(fn() => ['nice post'], group: 'social'),
                'posts'         => Inertia::merge([['id' => 1, 'title' => 'Post 1']], matchOn: 'id'),
                'notifications' => Inertia::prepend([['id' => 101, 'msg' => 'Hello']]),
            ],
            version: 'v1'
        );

        $this->assertTrue($decision->isJson());
        $page = $decision->pageObject->toArray();

        // 1. Regular closures and Always props are evaluated
        $this->assertSame(['name' => 'Alice'], $page['props']['user']);
        $this->assertSame('site_online', $page['props']['alwaysInfo']);

        // 2. Lazy props are omitted on full visits
        $this->assertArrayNotHasKey('lazyProp', $page['props']);

        // 3. Deferred props are announced in metadata and omitted from props
        $this->assertArrayNotHasKey('deferredComments', $page['props']);
        $this->assertSame(['social' => ['deferredComments']], $page['deferredProps']);

        // 4. Mergeable props are evaluated and labeled
        $this->assertSame([['id' => 1, 'title' => 'Post 1']], $page['props']['posts']);
        $this->assertSame(['posts'], $page['mergeProps']);
        $this->assertSame(['posts.id'], $page['matchPropsOn']);
        $this->assertSame(['notifications'], $page['prependProps']);
    }

    public function testOncePropsClientCaching(): void
    {
        // First full visit: client does not have 'plans' cached
        $request1 = new NativeRequest(headers: ['x-inertia' => 'true'], url: '/billing');
        $decision1 = $this->engine->evaluate($request1, 'Billing/Plans', [
            'plans' => Inertia::once(fn() => ['Basic', 'Pro'], expiresAt: 1700000000),
        ]);

        $page1 = $decision1->pageObject->toArray();
        $this->assertSame(['Basic', 'Pro'], $page1['props']['plans']);
        $this->assertSame(['plans' => ['prop' => 'plans', 'expiresAt' => 1700000000]], $page1['onceProps']);

        // Subsequent visit: client sends X-Inertia-Except-Once-Props: plans
        $request2 = new NativeRequest(
            headers: [
                'x-inertia' => 'true',
                'x-inertia-except-once-props' => 'plans',
            ],
            url: '/billing/upgrade'
        );
        $decision2 = $this->engine->evaluate($request2, 'Billing/Upgrade', [
            'plans' => Inertia::once(fn() => ['Basic', 'Pro'], expiresAt: 1700000000),
            'user'  => 'Alice',
        ]);

        $page2 = $decision2->pageObject->toArray();
        // 'plans' is skipped from props but announced in onceProps
        $this->assertArrayNotHasKey('plans', $page2['props']);
        $this->assertSame('Alice', $page2['props']['user']);
        $this->assertSame(['plans' => ['prop' => 'plans', 'expiresAt' => 1700000000]], $page2['onceProps']);
    }

    public function testInfiniteScrollPropMetadata(): void
    {
        $request = new NativeRequest(headers: ['x-inertia' => 'true'], url: '/posts?page=1');
        $decision = $this->engine->evaluate($request, 'Posts/Index', [
            'posts' => Inertia::scroll(
                data: [['id' => 1, 'title' => 'First']],
                pageName: 'page',
                nextPage: 2,
                currentPage: 1
            ),
        ]);

        $page = $decision->pageObject->toArray();
        $this->assertSame([['id' => 1, 'title' => 'First']], $page['props']['posts']);
        $this->assertSame(['posts.data'], $page['mergeProps']);
        $this->assertSame([
            'posts' => [
                'pageName'     => 'page',
                'previousPage' => null,
                'nextPage'     => 2,
                'currentPage'  => 1,
                'reset'        => false,
            ],
        ], $page['scrollProps']);
    }
}
