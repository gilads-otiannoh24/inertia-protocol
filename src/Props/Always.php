<?php

declare(strict_types=1);

namespace Inertia\Protocol\Props;

use Inertia\Protocol\Contracts\PropInterface;
use Inertia\Protocol\Support\Arr;

class Always implements PropInterface
{
    public function __construct(public mixed $value)
    {
    }

    public function resolve(): mixed
    {
        return Arr::value($this->value);
    }
}
