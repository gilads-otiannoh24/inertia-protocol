<?php

declare(strict_types=1);

namespace Inertia\Protocol\Tests\Unit;

use Inertia\Protocol\Inertia;
use Inertia\Protocol\ProtocolEngine;
use Inertia\Protocol\Support\NativeRequest;
use PHPUnit\Framework\TestCase;

class InfiniteScrollMergeIntentTest extends TestCase
{
    private ProtocolEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new ProtocolEngine();
    }

    public function testScrollPropWithPrependMergeIntentEmitsPrependProps(): void
    {
        $request = new NativeRequest(
            headers: [
                'x-inertia'                               => 'true',
                'x-inertia-infinite-scroll-merge-intent' => 'prepend',
            ],
            url: '/chat?page=2'
        );

        $decision = $this->engine->evaluate(
            request: $request,
            component: 'Chat/Room',
            props: [
                'messages' => Inertia::scroll([['id' => 1, 'text' => 'Older message']], currentPage: 2),
            ]
        );

        $page = $decision->pageObject->toArray();

        $this->assertArrayHasKey('prependProps', $page);
        $this->assertSame(['messages.data'], $page['prependProps']);
        $this->assertArrayNotHasKey('mergeProps', $page);
    }

    public function testScrollPropWithAppendMergeIntentEmitsMergeProps(): void
    {
        $request = new NativeRequest(
            headers: [
                'x-inertia'                               => 'true',
                'x-inertia-infinite-scroll-merge-intent' => 'append',
            ],
            url: '/feed?page=2'
        );

        $decision = $this->engine->evaluate(
            request: $request,
            component: 'Feed/Index',
            props: [
                'items' => Inertia::scroll([['id' => 20]], currentPage: 2),
            ]
        );

        $page = $decision->pageObject->toArray();

        $this->assertArrayHasKey('mergeProps', $page);
        $this->assertSame(['items.data'], $page['mergeProps']);
        $this->assertArrayNotHasKey('prependProps', $page);
    }
}
