<?php

declare(strict_types=1);

namespace Inertia\Protocol\Tests\Unit;

use Inertia\Protocol\PageObject;
use PHPUnit\Framework\TestCase;

class HtmlResponseScriptTest extends TestCase
{
    public function testHtmlScriptRenderingFollowsV3Spec(): void
    {
        $page = new PageObject(
            component: 'Event',
            props: [
                'event' => [
                    'id'    => 80,
                    'title' => 'Birthday party',
                ],
            ],
            url: '/events/80',
            version: 'hash123'
        );

        $script = $page->toHtmlScript('app');
        $tag = $page->toHtmlTag('app');
        $fullHtml = $page->toHtml('app');

        $this->assertStringStartsWith('<script data-page="app" type="application/json">', $script);
        $this->assertStringEndsWith('</script>', $script);
        $this->assertSame('<div id="app"></div>', $tag);
        $this->assertSame($script . $tag, $fullHtml);
    }

    public function testHtmlScriptEscapesScriptTagsAndForwardSlashesPerSpec(): void
    {
        $page = new PageObject(
            component: 'Malicious/Script',
            props: [
                'malicious' => '</script><script>alert("xss")</script>',
            ],
            url: '/events/80',
            version: 'v1'
        );

        $htmlScript = $page->toHtmlScript('app');

        // Forward slashes in URLs must be escaped as \/
        $this->assertStringContainsString('\/events\/80', $htmlScript);

        // Literal </script> sequence must never appear inside the script element
        $this->assertStringNotContainsString('</script><script>', $htmlScript);
        $this->assertStringContainsString('\u003C\/script\u003E', $htmlScript);
    }
}
