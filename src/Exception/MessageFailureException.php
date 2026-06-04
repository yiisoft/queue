<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Exception;

use RuntimeException;
use Throwable;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Message\IdEnvelope;

use function sprintf;

final class MessageFailureException extends RuntimeException
{
    public function __construct(
        private readonly MessageInterface $queueMessage,
        Throwable $previous,
    ) {
        $messageId = IdEnvelope::fromMessage($queueMessage)->getId();
        $exceptionMessage = sprintf(
            "Processing of message %s is stopped because of an exception:\n%s.",
            $messageId === null ? 'without ID' : "#$messageId",
            $previous->getMessage(),
        );

        parent::__construct($exceptionMessage, 0, $previous);
    }

    public function getQueueMessage(): MessageInterface
    {
        return $this->queueMessage;
    }
}
