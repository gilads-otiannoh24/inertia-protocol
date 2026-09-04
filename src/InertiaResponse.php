<?php

declare(strict_types=1);

namespace Inertia\Protocol;

use Inertia\Protocol\Contracts\RequestInterface;
use Inertia\Protocol\Support\NativeRequest;

class InertiaResponse
{
    /**
     * @var array<string, mixed>
     */
    protected array $props = [];

    protected mixed $version = '';

    /**
     * @var array<string, mixed>
     */
    protected array $options = [];

    protected ProtocolEngine $engine;

    /**
     * @param string $component JavaScript page component name
     * @param array<string, mixed> $props Initial props
     * @param mixed $version Asset version
     */
    public function __construct(
        protected string $component,
        array $props = [],
        mixed $version = ''
    ) {
        $this->props = $props;
        $this->version = $version;
        $this->engine = new ProtocolEngine();
    }

    /**
     * Add one or multiple props to the response.
     *
     * @param array<string, mixed>|string $key
     */
    public function with(array|string $key, mixed $value = null): self
    {
        if (is_array($key)) {
            $this->props = array_merge($this->props, $key);
        } else {
            $this->props[$key] = $value;
        }

        return $this;
    }

    /**
     * Set the asset version.
     */
    public function withVersion(mixed $version): self
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Instruct client to encrypt the page's history state.
     */
    public function encryptHistory(bool $encrypt = true): self
    {
        $this->options['encryptHistory'] = $encrypt;

        return $this;
    }

    /**
     * Instruct client to clear encrypted history state.
     */
    public function clearHistory(bool $clear = true): self
    {
        $this->options['clearHistory'] = $clear;

        return $this;
    }

    /**
     * Instruct client to preserve URL fragment across a redirect.
     */
    public function preserveFragment(bool $preserve = true): self
    {
        $this->options['preserveFragment'] = $preserve;

        return $this;
    }

    /**
     * Set shared prop keys to be announced in sharedProps.
     *
     * @param list<string> $keys
     */
    public function withSharedKeys(array $keys): self
    {
        $this->options['sharedKeys'] = $keys;

        return $this;
    }

    /**
     * Set flash session data for the response.
     *
     * @param array<string, mixed> $flash
     */
    public function withFlash(array $flash): self
    {
        $this->options['flash'] = $flash;

        return $this;
    }

    /**
     * Evaluate the protocol against the request and return the ResponseDecision.
     */
    public function toDecision(?RequestInterface $request = null): ResponseDecision
    {
        $req = $request ?? NativeRequest::fromGlobals();

        return $this->engine->evaluate($req, $this->component, $this->props, $this->version, $this->options);
    }
}
