<?php

declare(strict_types=1);

namespace Inertia\Protocol\Support;

use Inertia\Protocol\Exceptions\ReservedPropException;
use Inertia\Protocol\ProtocolVersion;

final class ReservedProps
{
    public const PROP_ERRORS = 'errors';
    public const PROP_FLASH = 'flash';
    public const PROP_INERTIA = '__inertia';

    protected static bool $enabled = true;

    /**
     * @var array<string, list<string>>
     */
    protected static array $defaultReserved = [
        '2.0' => [
            self::PROP_ERRORS,
            self::PROP_FLASH,
        ],
        '3.0' => [
            self::PROP_ERRORS,
            self::PROP_FLASH,
            self::PROP_INERTIA,
        ],
    ];

    /**
     * @var array<string, list<string>>
     */
    protected static array $reservedByVersion = [
        '2.0' => [
            self::PROP_ERRORS,
            self::PROP_FLASH,
        ],
        '3.0' => [
            self::PROP_ERRORS,
            self::PROP_FLASH,
            self::PROP_INERTIA,
        ],
    ];

    /**
     * Enable reserved prop validation.
     */
    public static function enable(): void
    {
        self::$enabled = true;
    }

    /**
     * Disable reserved prop validation.
     */
    public static function disable(): void
    {
        self::$enabled = false;
    }

    /**
     * Check if reserved prop validation is enabled.
     */
    public static function isEnabled(): bool
    {
        return self::$enabled;
    }

    /**
     * Get reserved prop keys for a given protocol version.
     *
     * @param ProtocolVersion|string $version
     * @return list<string>
     */
    public static function getReservedKeys(ProtocolVersion|string $version = ProtocolVersion::V3): array
    {
        $ver = ProtocolVersion::fromMixed($version);
        return self::$reservedByVersion[$ver->value] ?? self::$reservedByVersion['3.0'];
    }

    /**
     * Check if a given prop key is reserved.
     */
    public static function isReserved(string $key, ProtocolVersion|string $version = ProtocolVersion::V3): bool
    {
        return in_array($key, self::getReservedKeys($version), true);
    }

    /**
     * Validate an array of incoming user props against reserved keys.
     *
     * @param array<string|int, mixed> $props
     * @param ProtocolVersion|string $version
     * @throws ReservedPropException
     */
    public static function validate(array $props, ProtocolVersion|string $version = ProtocolVersion::V3): void
    {
        if (!self::$enabled) {
            return;
        }

        $reserved = self::getReservedKeys($version);
        $ver = ProtocolVersion::fromMixed($version);

        foreach (array_keys($props) as $key) {
            $keyStr = (string) $key;
            if (in_array($keyStr, $reserved, true)) {
                throw new ReservedPropException(
                    key: $keyStr,
                    version: $ver->value
                );
            }
        }
    }

    /**
     * Add a custom reserved prop key.
     */
    public static function addReservedKey(string $key, ProtocolVersion|string|null $version = null): void
    {
        if ($version === null) {
            foreach (self::$reservedByVersion as $ver => $keys) {
                if (!in_array($key, $keys, true)) {
                    self::$reservedByVersion[$ver][] = $key;
                }
            }
            return;
        }

        $ver = ProtocolVersion::fromMixed($version)->value;
        if (!isset(self::$reservedByVersion[$ver])) {
            self::$reservedByVersion[$ver] = [];
        }
        if (!in_array($key, self::$reservedByVersion[$ver], true)) {
            self::$reservedByVersion[$ver][] = $key;
        }
    }

    /**
     * Remove a reserved prop key.
     */
    public static function removeReservedKey(string $key, ProtocolVersion|string|null $version = null): void
    {
        if ($version === null) {
            foreach (self::$reservedByVersion as $ver => $keys) {
                self::$reservedByVersion[$ver] = array_values(array_filter($keys, fn($k) => $k !== $key));
            }
            return;
        }

        $ver = ProtocolVersion::fromMixed($version)->value;
        if (isset(self::$reservedByVersion[$ver])) {
            self::$reservedByVersion[$ver] = array_values(array_filter(self::$reservedByVersion[$ver], fn($k) => $k !== $key));
        }
    }

    /**
     * Reset reserved prop keys back to their defaults.
     */
    public static function reset(): void
    {
        self::$enabled = true;
        self::$reservedByVersion = self::$defaultReserved;
    }
}
