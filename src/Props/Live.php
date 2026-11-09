<?php

declare(strict_types=1);

namespace Inertia\Protocol\Props;

use Inertia\Protocol\Contracts\PropInterface;
use Inertia\Protocol\Support\Arr;

class Live implements PropInterface
{
    /**
     * @var list<array{channel: array{name: string, type: string}, events: list<string>}>
     */
    public array $listeners = [];

    /**
     * @param mixed $value Underlying prop value or callback
     * @param list<array{channel: array{name: string, type?: string}|string, events: list<string>|string}> $listeners
     * @param int|null $throttle Throttle in milliseconds
     */
    public function __construct(
        public mixed $value,
        array $listeners = [],
        public ?int $throttle = null
    ) {
        foreach ($listeners as $listener) {
            $this->addListener($listener['channel'], $listener['events']);
        }
    }

    /**
     * Add a broadcast listener for this live prop.
     *
     * @param array{name: string, type?: string}|string $channel Channel name (string) or array with 'name' and 'type'
     * @param list<string>|string $events
     * @param string $type Channel type: 'public', 'private', 'presence' (default: 'private')
     */
    public function listen(array|string $channel, array|string $events, string $type = 'private'): self
    {
        $this->addListener($channel, $events, $type);
        return $this;
    }

    /**
     * Set the update throttle in milliseconds.
     */
    public function throttle(int $milliseconds): self
    {
        $this->throttle = $milliseconds;
        return $this;
    }

    /**
     * Chain a defer configuration onto this live prop.
     */
    public function defer(string $group = 'default', bool $rescue = false): Defer
    {
        return new Defer($this, $group, $rescue);
    }

    protected function addListener(array|string $channel, array|string $events, string $type = 'private'): void
    {
        if (is_string($channel)) {
            $channelData = ['name' => $channel, 'type' => $type];
        } else {
            $channelData = [
                'name' => $channel['name'],
                'type' => $channel['type'] ?? $type,
            ];
        }

        $eventList = is_array($events) ? array_values($events) : [$events];

        $this->listeners[] = [
            'channel' => $channelData,
            'events'  => $eventList,
        ];
    }

    public function resolve(): mixed
    {
        return Arr::resolve($this->value);
    }

    /**
     * @return array{listeners: list<array{channel: array{name: string, type: string}, events: list<string>}>, throttle?: int}
     */
    public function toMetadata(): array
    {
        $metadata = [
            'listeners' => $this->listeners,
        ];

        if ($this->throttle !== null) {
            $metadata['throttle'] = $this->throttle;
        }

        return $metadata;
    }
}
