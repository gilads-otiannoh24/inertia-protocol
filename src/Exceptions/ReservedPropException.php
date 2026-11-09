<?php

declare(strict_types=1);

namespace Inertia\Protocol\Exceptions;

use LogicException;

class ReservedPropException extends LogicException
{
    /**
     * @param string $key The reserved prop key that was attempted to be used
     * @param string $version The active wire protocol version
     * @param string $message
     * @param \Throwable|null $previous
     */
    public function __construct(
        public readonly string $key,
        public readonly string $version,
        string $message = '',
        ?\Throwable $previous = null
    ) {
        if ($message === '') {
            $recommendation = match ($key) {
                'errors' => 'Use withErrors() on the response to attach validation errors.',
                'flash'  => 'Use withFlash() on the response or the flash session handler to attach flash data.',
                default  => 'Choose a different prop key to avoid colliding with the Inertia.js frontend protocol.',
            };

            $message = sprintf(
                'Cannot use reserved prop key "%s". This key is required and reserved by the Inertia.js (Protocol %s) frontend for internal operation. %s',
                $key,
                $version,
                $recommendation
            );
        }

        parent::__construct($message, 0, $previous);
    }
}
