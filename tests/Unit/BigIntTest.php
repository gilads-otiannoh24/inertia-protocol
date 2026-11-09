<?php

declare(strict_types=1);

namespace Inertia\Protocol\Tests\Unit;

use Inertia\Protocol\Inertia;
use Inertia\Protocol\ProtocolEngine;
use Inertia\Protocol\Support\Arr;
use Inertia\Protocol\Support\NativeRequest;
use PHPUnit\Framework\TestCase;

class BigIntTest extends TestCase
{
    private ProtocolEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new ProtocolEngine();
    }

    public function testBigIntWrappingOnlyWhenPreserveBigIntegersEnabled(): void
    {
        $safeInt = 42;
        $largeInt = 9223372036854775807; // Exceeds JS safe range (9007199254740991)
        $negativeLargeInt = -9223372036854775807;

        $request = new NativeRequest(headers: ['x-inertia' => 'true'], url: '/data');

        // 1. When disabled, integers remain untouched and preserveBigIntegers is not emitted
        $decisionDisabled = $this->engine->evaluate(
            request: $request,
            component: 'Data/Show',
            props: [
                'safe'     => $safeInt,
                'large'    => $largeInt,
            ],
            options: ['preserveBigIntegers' => false]
        );

        $pageDisabled = $decisionDisabled->pageObject->toArray();
        $this->assertSame($largeInt, $pageDisabled['props']['large']);
        $this->assertArrayNotHasKey('preserveBigIntegers', $pageDisabled);

        // 2. When enabled, large integers are wrapped and preserveBigIntegers is true
        $decisionEnabled = $this->engine->evaluate(
            request: $request,
            component: 'Data/Show',
            props: [
                'safe'     => $safeInt,
                'large'    => $largeInt,
                'nested'   => ['neg' => $negativeLargeInt],
            ],
            options: ['preserveBigIntegers' => true]
        );

        $pageEnabled = $decisionEnabled->pageObject->toArray();
        $this->assertTrue($pageEnabled['preserveBigIntegers']);
        $this->assertSame(42, $pageEnabled['props']['safe']);
        $this->assertSame(['$bigint' => (string) $largeInt], $pageEnabled['props']['large']);
        $this->assertSame(['$bigint' => (string) $negativeLargeInt], $pageEnabled['props']['nested']['neg']);
    }

    public function testArrHelperWrapBigIntegersDirectly(): void
    {
        $wrapped = Arr::wrapBigIntegers([
            'id' => 9007199254740992,
            'normal' => 100,
        ]);

        $this->assertSame(['$bigint' => '9007199254740992'], $wrapped['id']);
        $this->assertSame(100, $wrapped['normal']);
    }
}
