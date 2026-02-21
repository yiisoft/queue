<?php

declare(strict_types=1);

namespace Yiisoft\Queue;

use BackedEnum;

/**
 * @internal
 */
final class StringNormalizer
{
    public static function normalize(string|BackedEnum $queueName): string
    {
        return $queueName instanceof BackedEnum ? (string) $queueName->value : $queueName;
    }
}
