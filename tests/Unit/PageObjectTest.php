<?php

declare(strict_types=1);

namespace Inertia\Protocol\Tests\Unit;

use Inertia\Protocol\PageObject;
use PHPUnit\Framework\TestCase;

class PageObjectTest extends TestCase
{
    public function testMinimalPageObjectStructure(): void
    {
        $page = new PageObject(
            component: 'Users/Index',
            props: ['users' => [['id' => 1, 'name' => 'Alice']]],
            url: '/users',
            version: 'v1.0.0'
        );

        $array = $page->toArray();

        $this->assertSame('Users/Index', $array['component']);
        $this->assertSame('/users', $array['url']);
        $this->assertSame('v1.0.0', $array['version']);
        $this->assertSame([['id' => 1, 'name' => 'Alice']], $array['props']['users']);
        $this->assertEquals((object) [], $array['props']['errors']);

        // Conditional metadata must be omitted when empty
        $this->assertArrayNotHasKey('deferredProps', $array);
        $this->assertArrayNotHasKey('rescuedProps', $array);
        $this->assertArrayNotHasKey('mergeProps', $array);
        $this->assertArrayNotHasKey('prependProps', $array);
        $this->assertArrayNotHasKey('deepMergeProps', $array);
        $this->assertArrayNotHasKey('matchPropsOn', $array);
        $this->assertArrayNotHasKey('onceProps', $array);
        $this->assertArrayNotHasKey('scrollProps', $array);
        $this->assertArrayNotHasKey('encryptHistory', $array);
        $this->assertArrayNotHasKey('clearHistory', $array);
        $this->assertArrayNotHasKey('preserveFragment', $array);
    }

    public function testPageObjectEmitsConditionalMetadata(): void
    {
        $page = new PageObject(
            component: 'Posts/Show',
            props: ['post' => ['id' => 10]],
            url: '/posts/10',
            version: 'hash123',
            deferredProps: ['default' => ['comments']],
            mergeProps: ['comments'],
            encryptHistory: true,
            preserveFragment: true
        );

        $array = $page->toArray();

        $this->assertSame(['default' => ['comments']], $array['deferredProps']);
        $this->assertSame(['comments'], $array['mergeProps']);
        $this->assertTrue($array['encryptHistory']);
        $this->assertTrue($array['preserveFragment']);
        $this->assertArrayNotHasKey('clearHistory', $array);
    }
}
