<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Provider;

use BackedEnum;
use LogicException;
use Throwable;
use Yiisoft\Queue\QueueNameNormalizer;

use function sprintf;

/**
 * Thrown when the queue is not found.
 */
final class QueueNotFoundException extends LogicException implements QueueProviderException
{
    public function __construct(string|BackedEnum $queueName, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct(
            sprintf('Queue with name "%s" not found.', QueueNameNormalizer::normalize($queueName)),
            $code,
            $previous,
        );
    }
}
