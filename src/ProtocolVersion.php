<?php

declare(strict_types=1);

namespace Inertia\Protocol;

enum ProtocolVersion: string
{
    case V2 = '2.0';
    case V3 = '3.0';

    /**
     * Resolve a ProtocolVersion from an enum, string, or integer.
     */
    public static function fromMixed(mixed $version): self
    {
        if ($version instanceof self) {
            return $version;
        }

        if (is_int($version) || is_float($version)) {
            $version = (string) $version;
        }

        if (is_string($version)) {
            $clean = strtolower(trim($version));
            if (in_array($clean, ['2', '2.0', 'v2', 'version 2'], true)) {
                return self::V2;
            }
            if (in_array($clean, ['3', '3.0', 'v3', 'version 3'], true)) {
                return self::V3;
            }
        }

        return self::V3;
    }

    public function isV2(): bool
    {
        return $this === self::V2;
    }

    public function isV3(): bool
    {
        return $this === self::V3;
    }
}
