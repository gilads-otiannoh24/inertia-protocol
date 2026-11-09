<?php

declare(strict_types=1);

namespace Inertia\Protocol\Tests\Unit;

use Inertia\Protocol\Inertia;
use Inertia\Protocol\ProtocolEngine;
use Inertia\Protocol\Support\NativeRequest;
use PHPUnit\Framework\TestCase;

class ComposablePropsTest extends TestCase
{
    private ProtocolEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new ProtocolEngine();
    }

    public function testDeferredAndMergeablePropOnFullVisitAndPartialReload(): void
    {
        $feedProp = Inertia::defer(fn() => [['id' => 1, 'title' => 'Post 1']])->merge('id');

        // 1. Full visit: deferred prop is announced in deferredProps, NOT in props or mergeProps
        $fullRequest = new NativeRequest(headers: ['x-inertia' => 'true'], url: '/feed');
        $fullDecision = $this->engine->evaluate($fullRequest, 'Feed/Index', ['feed' => $feedProp]);
        $fullPage = $fullDecision->pageObject->toArray();

        $this->assertArrayNotHasKey('feed', $fullPage['props']);
        $this->assertSame(['default' => ['feed']], $fullPage['deferredProps']);
        $this->assertArrayNotHasKey('mergeProps', $fullPage);

        // 2. Partial reload requesting 'feed': resolved and tagged for merging
        $partialRequest = new NativeRequest(
            headers: [
                'x-inertia'                   => 'true',
                'x-inertia-partial-component' => 'Feed/Index',
                'x-inertia-partial-data'      => 'feed',
            ],
            url: '/feed'
        );
        $partialDecision = $this->engine->evaluate($partialRequest, 'Feed/Index', ['feed' => $feedProp]);
        $partialPage = $partialDecision->pageObject->toArray();

        $this->assertSame([['id' => 1, 'title' => 'Post 1']], $partialPage['props']['feed']);
        $this->assertSame(['feed'], $partialPage['mergeProps']);
        $this->assertSame(['feed.id'], $partialPage['matchPropsOn']);
        $this->assertArrayNotHasKey('deferredProps', $partialPage);
    }

    public function testDeferredScrollPropOnFullVisitAndPartialReload(): void
    {
        $scrollProp = Inertia::defer(
            fn() => Inertia::scroll(
                data: [['id' => 10]],
                pageName: 'page',
                nextPage: 2,
                currentPage: 1
            )
        );

        // Full visit: deferred scroll prop emits no scrollProps on full visit
        $fullRequest = new NativeRequest(headers: ['x-inertia' => 'true'], url: '/posts');
        $fullDecision = $this->engine->evaluate($fullRequest, 'Posts', ['posts' => $scrollProp]);
        $fullPage = $fullDecision->pageObject->toArray();

        $this->assertArrayNotHasKey('posts', $fullPage['props']);
        $this->assertSame(['default' => ['posts']], $fullPage['deferredProps']);
        $this->assertArrayNotHasKey('scrollProps', $fullPage);
        $this->assertArrayNotHasKey('mergeProps', $fullPage);

        // Partial reload: emits scrollProps and mergeProps
        $partialRequest = new NativeRequest(
            headers: [
                'x-inertia'                   => 'true',
                'x-inertia-partial-component' => 'Posts',
                'x-inertia-partial-data'      => 'posts',
            ],
            url: '/posts'
        );
        $partialDecision = $this->engine->evaluate($partialRequest, 'Posts', ['posts' => $scrollProp]);
        $partialPage = $partialDecision->pageObject->toArray();

        $this->assertSame([['id' => 10]], $partialPage['props']['posts']);
        $this->assertSame(['posts.data'], $partialPage['mergeProps']);
        $this->assertSame(1, $partialPage['scrollProps']['posts']['currentPage']);
    }

    public function testOptionalAliasMatchesLazy(): void
    {
        $optProp = Inertia::optional(fn() => 'optional_data');

        // Full visit: skipped
        $fullRequest = new NativeRequest(headers: ['x-inertia' => 'true'], url: '/home');
        $fullDecision = $this->engine->evaluate($fullRequest, 'Home', ['opt' => $optProp]);
        $this->assertArrayNotHasKey('opt', $fullDecision->pageObject->toArray()['props']);

        // Partial reload: resolved
        $partialRequest = new NativeRequest(
            headers: [
                'x-inertia'                   => 'true',
                'x-inertia-partial-component' => 'Home',
                'x-inertia-partial-data'      => 'opt',
            ],
            url: '/home'
        );
        $partialDecision = $this->engine->evaluate($partialRequest, 'Home', ['opt' => $optProp]);
        $this->assertSame('optional_data', $partialDecision->pageObject->toArray()['props']['opt']);
    }
}
