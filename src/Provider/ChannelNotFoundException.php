<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Provider;

use LogicException;
use Throwable;

use function sprintf;

/**
 * Thrown when channel is not found.
 */
final class ChannelNotFoundException extends LogicException implements QueueProviderException
{
    public function __construct(string $channel, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct(
            sprintf('Channel "%s" not found.', $channel),
            $code,
            $previous,
        );
    }
}
