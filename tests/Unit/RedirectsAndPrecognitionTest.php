<?php

declare(strict_types=1);

namespace Inertia\Protocol\Tests\Unit;

use Inertia\Protocol\Inertia;
use Inertia\Protocol\ResponseDecision;
use Inertia\Protocol\Support\InertiaHeaders;
use PHPUnit\Framework\TestCase;

class RedirectsAndPrecognitionTest extends TestCase
{
    public function testExternalLocationVisitDecision(): void
    {
        $decision = Inertia::location('https://external-service.com/checkout');

        $this->assertSame(409, $decision->statusCode);
        $this->assertSame(ResponseDecision::TYPE_CONFLICT_LOCATION, $decision->type);
        $this->assertSame('https://external-service.com/checkout', $decision->headers[InertiaHeaders::HEADER_LOCATION]);
    }

    public function testFragmentRedirectDecision(): void
    {
        $decision = Inertia::redirectWithFragment('https://example.com/posts#comment-42');

        $this->assertSame(409, $decision->statusCode);
        $this->assertSame(ResponseDecision::TYPE_CONFLICT_REDIRECT, $decision->type);
        $this->assertSame('https://example.com/posts#comment-42', $decision->headers[InertiaHeaders::HEADER_REDIRECT]);
    }

    public function testPrecognitionSuccessDecision(): void
    {
        $decision = Inertia::precognitionSuccess();

        $this->assertSame(204, $decision->statusCode);
        $this->assertSame(ResponseDecision::TYPE_PRECOGNITION_SUCCESS, $decision->type);
        $this->assertSame('true', $decision->headers[InertiaHeaders::HEADER_PRECOGNITION]);
        $this->assertSame('true', $decision->headers[InertiaHeaders::HEADER_PRECOGNITION_SUCCESS]);
        $this->assertSame('Precognition', $decision->headers['Vary']);
    }
}
