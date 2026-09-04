<?php

declare(strict_types=1);

namespace Inertia\Protocol\Tests\Unit;

use Inertia\Protocol\ProtocolEngine;
use Inertia\Protocol\ResponseDecision;
use Inertia\Protocol\Support\InertiaHeaders;
use Inertia\Protocol\Support\NativeRequest;
use PHPUnit\Framework\TestCase;

class VersionMismatchTest extends TestCase
{
    private ProtocolEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new ProtocolEngine();
    }

    public function testGetRequestWithMismatchedVersionReturns409Conflict(): void
    {
        $request = new NativeRequest(
            headers: [
                'x-inertia'         => 'true',
                'x-inertia-version' => 'old_hash_111',
            ],
            method: 'GET',
            url: '/events/80'
        );

        $decision = $this->engine->evaluate(
            request: $request,
            component: 'Events',
            props: ['events' => []],
            version: 'new_hash_222'
        );

        $this->assertSame(409, $decision->statusCode);
        $this->assertSame(ResponseDecision::TYPE_CONFLICT_VERSION, $decision->type);
        $this->assertSame('/events/80', $decision->headers[InertiaHeaders::HEADER_LOCATION]);
        $this->assertSame('new_hash_222', $decision->headers[InertiaHeaders::HEADER_VERSION]);
    }

    public function testGetRequestWithMatchingVersionReturns200Json(): void
    {
        $request = new NativeRequest(
            headers: [
                'x-inertia'         => 'true',
                'x-inertia-version' => 'current_hash_333',
            ],
            method: 'GET',
            url: '/events/80'
        );

        $decision = $this->engine->evaluate(
            request: $request,
            component: 'Events',
            props: ['events' => []],
            version: 'current_hash_333'
        );

        $this->assertSame(200, $decision->statusCode);
        $this->assertSame(ResponseDecision::TYPE_PAGE_JSON, $decision->type);
    }

    public function testNonGetRequestDoesNotTriggerVersionMismatch409(): void
    {
        $request = new NativeRequest(
            headers: [
                'x-inertia'         => 'true',
                'x-inertia-version' => 'old_hash_111',
            ],
            method: 'POST',
            url: '/events'
        );

        $decision = $this->engine->evaluate(
            request: $request,
            component: 'Events',
            props: ['events' => []],
            version: 'new_hash_222'
        );

        $this->assertSame(200, $decision->statusCode);
        $this->assertSame(ResponseDecision::TYPE_PAGE_JSON, $decision->type);
    }
}
