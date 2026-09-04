<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message\Handler;

use LogicException;
use Throwable;

use function sprintf;

/**
 * Thrown when a handler for the given message type is not found.
 */
final class HandlerNotFoundException extends LogicException
{
    public function __construct(string $messageType, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct(
            sprintf('Queue handler for message type "%s" does not exist.', $messageType),
            $code,
            $previous,
        );
    }
}
