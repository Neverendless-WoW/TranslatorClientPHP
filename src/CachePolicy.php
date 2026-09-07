<?php

declare(strict_types=1);

namespace Neverendless\Translator;

/**
 * Cache policy for translation requests.
 *
 * Controls how the service reads from and writes to its shared Redis cache.
 * The wire values are stable — do not change them.
 *
 * - NORMAL   (0): Read cache; write on successful AI translation. Use for public/global
 *                 chat where many players may send the same message.
 * - NO_STORE (1): Read cache; never write. Use for private messages, whispers, or
 *                 content where caching the translation is undesirable.
 * - BYPASS   (2): Skip Redis entirely — neither read nor write. Use for sensitive
 *                 content that must not touch shared storage.
 *
 * The caller selects the policy. An untrusted browser payload or player-supplied
 * value must never be forwarded directly as the policy — always resolve it
 * server-side based on the message type.
 */
final class CachePolicy
{
    /** Read cache; write on successful AI translation. */
    public const NORMAL   = 0;

    /** Read cache; never write. */
    public const NO_STORE = 1;

    /** Skip Redis entirely — neither read nor write. */
    public const BYPASS   = 2;

    private function __construct() {}

    /**
     * Return the wire string representation for the REST API.
     *
     * @param int $policy One of the CachePolicy constants
     */
    public static function toWire(int $policy): string
    {
        return match ($policy) {
            self::NORMAL   => 'normal',
            self::NO_STORE => 'no_store',
            self::BYPASS   => 'bypass',
            default        => throw new \InvalidArgumentException(
                "Unknown cache policy value: {$policy}"
            ),
        };
    }
}
