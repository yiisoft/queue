<?php

declare(strict_types=1);

namespace Yiisoft\Queue;

use BackedEnum;

/**
 * @internal
 */
final class StringNormalizer
{
    public static function normalize(string|BackedEnum $value): string
    {
        return $value instanceof BackedEnum ? (string) $value->value : $value;
    }
}
