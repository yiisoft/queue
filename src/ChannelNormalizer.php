<?php

declare(strict_types=1);

namespace Yiisoft\Queue;

use BackedEnum;

/**
 * @internal
 */
final class ChannelNormalizer
{
    public static function normalize(string|BackedEnum $channel): string
    {
        return $channel instanceof BackedEnum ? (string) $channel->value : $channel;
    }
}
