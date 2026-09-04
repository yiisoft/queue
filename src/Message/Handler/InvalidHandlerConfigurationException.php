<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message\Handler;

use LogicException;
use Throwable;

use function sprintf;

/**
 * Thrown when a handler for the given message type is configured incorrectly.
 */
final class InvalidHandlerConfigurationException extends LogicException
{
    public function __construct(string $messageType, ?string $additionalMessage = null, ?Throwable $previous = null)
    {
        $message = sprintf('Queue handler for message type "%s" is configured incorrectly.', $messageType);

        if ($additionalMessage !== null) {
            $message .= ' ' . $additionalMessage;
        }

        parent::__construct($message, 0, $previous);
    }
}
