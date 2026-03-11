<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Provider;

use BackedEnum;
use LogicException;
use Throwable;
use Yiisoft\Queue\StringNormalizer;

use function sprintf;

/**
 * Thrown when the queue is not found.
 */
final class QueueNotFoundException extends LogicException implements QueueProviderException
{
    public function __construct(string|BackedEnum $name, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct(
            sprintf('Queue with name "%s" not found.', StringNormalizer::normalize($name)),
            $code,
            $previous,
        );
    }
}
