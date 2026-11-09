<?php

declare(strict_types=1);

namespace Inertia\Protocol\Tests\Unit;

use Inertia\Protocol\Inertia;
use Inertia\Protocol\ResponseDecision;
use Inertia\Protocol\Support\NativeRequest;
use PHPUnit\Framework\TestCase;

class RedirectAndStatus303Test extends TestCase
{
    public function testStandardRedirectUses302ForGetAndPost(): void
    {
        $getRequest = new NativeRequest(method: 'GET', url: '/submit');
        $postRequest = new NativeRequest(method: 'POST', url: '/submit');

        $getDecision = Inertia::redirect('/dashboard', $getRequest);
        $this->assertSame(302, $getDecision->statusCode);
        $this->assertTrue($getDecision->isRedirect());
        $this->assertSame('/dashboard', $getDecision->headers['Location']);

        $postDecision = Inertia::redirect('/dashboard', $postRequest);
        $this->assertSame(302, $postDecision->statusCode);
        $this->assertSame('/dashboard', $postDecision->headers['Location']);
    }

    public function testRedirectConverts302To303ForPutPatchDeleteQuery(): void
    {
        $methods = ['PUT', 'PATCH', 'DELETE', 'QUERY'];

        foreach ($methods as $method) {
            $req = new NativeRequest(method: $method, url: '/resource/1');
            $decision = Inertia::redirect('/resource', $req);

            $this->assertSame(
                303,
                $decision->statusCode,
                "Expected method {$method} to convert redirect to 303 See Other"
            );
            $this->assertSame('/resource', $decision->headers['Location']);
            $this->assertSame(ResponseDecision::TYPE_REDIRECT, $decision->type);
        }
    }
}
