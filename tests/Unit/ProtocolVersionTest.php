<?php

declare(strict_types=1);

namespace Inertia\Protocol\Tests\Unit;

use Inertia\Protocol\InertiaProtocol;
use Inertia\Protocol\PageObject;
use Inertia\Protocol\ProtocolVersion;
use Inertia\Protocol\Support\NativeRequest;
use PHPUnit\Framework\TestCase;

class ProtocolVersionTest extends TestCase
{
    protected function tearDown(): void
    {
        InertiaProtocol::setProtocolVersion(ProtocolVersion::V3);
        parent::tearDown();
    }

    public function testProtocolVersionEnumResolution(): void
    {
        $this->assertSame(ProtocolVersion::V2, ProtocolVersion::fromMixed('2'));
        $this->assertSame(ProtocolVersion::V2, ProtocolVersion::fromMixed('2.0'));
        $this->assertSame(ProtocolVersion::V2, ProtocolVersion::fromMixed('v2'));
        $this->assertSame(ProtocolVersion::V2, ProtocolVersion::fromMixed(2));

        $this->assertSame(ProtocolVersion::V3, ProtocolVersion::fromMixed('3'));
        $this->assertSame(ProtocolVersion::V3, ProtocolVersion::fromMixed('3.0'));
        $this->assertSame(ProtocolVersion::V3, ProtocolVersion::fromMixed('v3'));
        $this->assertSame(ProtocolVersion::V3, ProtocolVersion::fromMixed(3));
        $this->assertSame(ProtocolVersion::V3, ProtocolVersion::fromMixed('unknown'));
    }

    public function testVersion2EmitsDataPageOnDivForHtml(): void
    {
        $page = new PageObject(
            component: 'Event',
            props: [
                'event' => ['id' => 80, 'title' => 'Birthday party'],
            ],
            url: '/events/80',
            version: 'hash123',
            protocolVersion: ProtocolVersion::V2
        );

        $html = $page->toHtml('app');

        $this->assertStringStartsWith('<div id="app" data-page=\'', $html);
        $this->assertStringEndsWith('\'></div>', $html);
        $this->assertStringNotContainsString('<script data-page="app"', $html);
    }

    public function testVersion3EmitsScriptTagAndMountingDivForHtml(): void
    {
        $page = new PageObject(
            component: 'Event',
            props: [
                'event' => ['id' => 80, 'title' => 'Birthday party'],
            ],
            url: '/events/80',
            version: 'hash123',
            protocolVersion: ProtocolVersion::V3
        );

        $html = $page->toHtml('app');

        $this->assertStringStartsWith('<script data-page="app" type="application/json">', $html);
        $this->assertStringEndsWith('<div id="app"></div>', $html);
    }

    public function testVersion2StripsV3SpecificMetadataFields(): void
    {
        $page = new PageObject(
            component: 'Orders/Show',
            props: ['order' => ['id' => 1]],
            url: '/orders/1',
            version: 'v1',
            deferredProps: ['default' => ['comments']],
            rescuedProps: ['failedProp'],
            mergeProps: ['comments'],
            liveProps: ['order' => ['listeners' => []]],
            preserveBigIntegers: true,
            preserveFragment: true,
            sharedProps: ['auth'],
            flash: ['success' => 'done'],
            protocolVersion: ProtocolVersion::V2
        );

        $array = $page->toArray();

        // V2 supported fields are preserved
        $this->assertSame(['default' => ['comments']], $array['deferredProps']);
        $this->assertSame(['comments'], $array['mergeProps']);

        // V3 specific fields MUST be stripped in V2 mode
        $this->assertArrayNotHasKey('liveProps', $array);
        $this->assertArrayNotHasKey('preserveBigIntegers', $array);
        $this->assertArrayNotHasKey('rescuedProps', $array);
        $this->assertArrayNotHasKey('preserveFragment', $array);
        $this->assertArrayNotHasKey('sharedProps', $array);
        $this->assertArrayNotHasKey('flash', $array);
    }

    public function testVersion3EmitsAllV3MetadataFields(): void
    {
        $page = new PageObject(
            component: 'Orders/Show',
            props: ['order' => ['id' => 1]],
            url: '/orders/1',
            version: 'v1',
            deferredProps: ['default' => ['comments']],
            rescuedProps: ['failedProp'],
            mergeProps: ['comments'],
            liveProps: ['order' => ['listeners' => []]],
            preserveBigIntegers: true,
            preserveFragment: true,
            sharedProps: ['auth'],
            flash: ['success' => 'done'],
            protocolVersion: ProtocolVersion::V3
        );

        $array = $page->toArray();

        $this->assertArrayHasKey('liveProps', $array);
        $this->assertArrayHasKey('preserveBigIntegers', $array);
        $this->assertArrayHasKey('rescuedProps', $array);
        $this->assertArrayHasKey('preserveFragment', $array);
        $this->assertArrayHasKey('sharedProps', $array);
        $this->assertArrayHasKey('flash', $array);
    }

    public function testRedirect303DifferenceBetweenV2AndV3(): void
    {
        $queryRequest = new NativeRequest(method: 'QUERY', url: '/search');

        // Under v2, QUERY stays 302 Found (v2 only converts PUT, PATCH, DELETE)
        $decisionV2 = InertiaProtocol::redirect('/search', $queryRequest, protocolVersion: ProtocolVersion::V2);
        $this->assertSame(302, $decisionV2->statusCode);

        // Under v3, QUERY is converted to 303 See Other
        $decisionV3 = InertiaProtocol::redirect('/search', $queryRequest, protocolVersion: ProtocolVersion::V3);
        $this->assertSame(303, $decisionV3->statusCode);
    }

    public function testResponseBuilderWithProtocolVersion(): void
    {
        $response = InertiaProtocol::render('Users/Index', ['users' => []])
            ->withProtocolVersion(ProtocolVersion::V2);

        $decision = $response->toDecision(new NativeRequest(url: '/users'));

        $this->assertTrue($decision->pageObject->protocolVersion->isV2());
        $this->assertStringStartsWith('<div id="app" data-page=\'', $decision->toHtml());
    }
}
