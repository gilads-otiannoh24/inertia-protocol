<?php

declare(strict_types=1);

namespace Inertia\Protocol\Tests\Unit;

use Inertia\Protocol\Inertia;
use Inertia\Protocol\InertiaProtocol;
use Inertia\Protocol\Props\Always;
use Inertia\Protocol\Props\Defer;
use Inertia\Protocol\Props\Lazy;
use Inertia\Protocol\Props\Live;
use Inertia\Protocol\Props\Mergeable;
use Inertia\Protocol\Props\Once;
use Inertia\Protocol\Props\Optional;
use Inertia\Protocol\Props\Scroll;
use Inertia\Protocol\ProtocolVersion;
use PHPUnit\Framework\TestCase;

class InertiaProtocolEntrypointTest extends TestCase
{
    public function testCanonicalInertiaProtocolFactories(): void
    {
        $this->assertInstanceOf(Always::class, InertiaProtocol::always('val'));
        $this->assertInstanceOf(Lazy::class, InertiaProtocol::lazy(fn() => 'lazy'));
        $this->assertInstanceOf(Optional::class, InertiaProtocol::optional(fn() => 'opt'));
        $this->assertInstanceOf(Defer::class, InertiaProtocol::defer(fn() => 'def'));
        $this->assertInstanceOf(Once::class, InertiaProtocol::once(fn() => 'once'));
        $this->assertInstanceOf(Live::class, InertiaProtocol::live('live'));
        $this->assertInstanceOf(Mergeable::class, InertiaProtocol::merge([]));
        $this->assertInstanceOf(Mergeable::class, InertiaProtocol::prepend([]));
        $this->assertInstanceOf(Mergeable::class, InertiaProtocol::deepMerge([]));
        $this->assertInstanceOf(Scroll::class, InertiaProtocol::scroll([]));
    }

    public function testBackwardCompatibleInertiaSubclass(): void
    {
        $this->assertTrue(is_subclass_of(Inertia::class, InertiaProtocol::class));

        // Methods invoked through Inertia work transparently
        $response = Inertia::render('Test', ['a' => 1]);
        $decision = $response->toDecision();

        $this->assertSame('Test', $decision->pageObject->component);
        $this->assertSame(1, $decision->pageObject->props['a']);
    }
}
