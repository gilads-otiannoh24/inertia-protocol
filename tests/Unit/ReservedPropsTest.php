<?php

declare(strict_types=1);

namespace Inertia\Protocol\Tests\Unit;

use Inertia\Protocol\Exceptions\ReservedPropException;
use Inertia\Protocol\InertiaProtocol;
use Inertia\Protocol\ProtocolVersion;
use Inertia\Protocol\Support\NativeRequest;
use Inertia\Protocol\Support\ReservedProps;
use PHPUnit\Framework\TestCase;

class ReservedPropsTest extends TestCase
{
    protected function tearDown(): void
    {
        ReservedProps::reset();
        InertiaProtocol::flushShared();
        InertiaProtocol::setProtocolVersion(ProtocolVersion::V3);
        parent::tearDown();
    }

    public function testPassingErrorsInPropsThrowsReservedPropException(): void
    {
        $this->expectException(ReservedPropException::class);
        $this->expectExceptionMessage('Cannot use reserved prop key "errors"');

        InertiaProtocol::render('Users/Create', [
            'errors' => ['email' => 'Invalid email'],
        ]);
    }

    public function testPassingFlashInPropsThrowsReservedPropExceptionInV3(): void
    {
        $this->expectException(ReservedPropException::class);
        $this->expectExceptionMessage('Cannot use reserved prop key "flash"');

        InertiaProtocol::render('Dashboard', [
            'flash' => ['success' => 'Welcome!'],
        ]);
    }

    public function testPassingInertiaInternalKeyThrowsReservedPropExceptionInV3(): void
    {
        $this->expectException(ReservedPropException::class);
        $this->expectExceptionMessage('Cannot use reserved prop key "__inertia"');

        InertiaProtocol::render('Dashboard', [
            '__inertia' => ['meta' => 123],
        ]);
    }

    public function testPassingFlashInPropsThrowsReservedPropExceptionInV2(): void
    {
        InertiaProtocol::setProtocolVersion(ProtocolVersion::V2);

        $this->expectException(ReservedPropException::class);
        $this->expectExceptionMessage('Cannot use reserved prop key "flash"');

        InertiaProtocol::render('Dashboard', [
            'flash' => 'some message',
        ]);
    }

    public function testResponseWithThrowsWhenPassingReservedKey(): void
    {
        $response = InertiaProtocol::render('Users/Show', ['user' => ['id' => 1]]);

        $this->expectException(ReservedPropException::class);
        $response->with('errors', ['test']);
    }

    public function testShareThrowsWhenSharingReservedPropKey(): void
    {
        $this->expectException(ReservedPropException::class);
        InertiaProtocol::share('errors', ['error']);
    }

    public function testWithErrorsAllowsSettingValidationErrorsSafely(): void
    {
        $response = InertiaProtocol::render('Users/Create', [
            'name' => 'Ian',
        ])->withErrors([
            'email' => 'Email is already taken.',
        ]);

        $decision = $response->toDecision(new NativeRequest(url: '/users/create'));
        $page = $decision->pageObject->toArray();

        $this->assertSame('Ian', $page['props']['name']);
        $this->assertSame(['email' => 'Email is already taken.'], $page['props']['errors']);
    }

    public function testWithFlashAllowsSettingFlashSafely(): void
    {
        $response = InertiaProtocol::render('Dashboard', [
            'stats' => [1, 2, 3],
        ])->withFlash([
            'message' => 'Profile updated successfully!',
        ]);

        $decision = $response->toDecision(new NativeRequest(url: '/dashboard'));
        $page = $decision->pageObject->toArray();

        $this->assertSame([1, 2, 3], $page['props']['stats']);
        $this->assertSame(['message' => 'Profile updated successfully!'], $page['flash']);
    }

    public function testCanDisableAndEnableReservedPropsProtection(): void
    {
        InertiaProtocol::protectReservedProps(false);

        // When disabled, no exception is thrown
        $response = InertiaProtocol::render('Dashboard', [
            'errors' => ['custom' => 'allowed when disabled'],
        ]);

        $this->assertSame('Dashboard', $response->toDecision()->pageObject->component);

        // Re-enable
        InertiaProtocol::protectReservedProps(true);

        $this->expectException(ReservedPropException::class);
        InertiaProtocol::render('Dashboard', ['errors' => 'fails']);
    }

    public function testCustomReservedKeysCanBeAddedAndRemoved(): void
    {
        ReservedProps::addReservedKey('page');
        $this->assertTrue(ReservedProps::isReserved('page'));

        $this->expectException(ReservedPropException::class);
        InertiaProtocol::render('Home', ['page' => 1]);
    }

    public function testExceptionPropertiesAndHelpfulRecommendationMessage(): void
    {
        try {
            InertiaProtocol::render('Test', ['errors' => 'error']);
            $this->fail('Expected ReservedPropException was not thrown.');
        } catch (ReservedPropException $e) {
            $this->assertSame('errors', $e->key);
            $this->assertSame('3.0', $e->version);
            $this->assertStringContainsString('Use withErrors() on the response to attach validation errors.', $e->getMessage());
        }
    }
}
