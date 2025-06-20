<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Exception;

use RuntimeException;
use Throwable;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Message\IdEnvelope;

final class JobFailureException extends RuntimeException
{
    public function __construct(
        private readonly MessageInterface $queueMessage,
        Throwable $previous
    ) {
        $error = $previous->getMessage();
        $messageId = $queueMessage->getMetadata()[IdEnvelope::MESSAGE_ID_KEY] ?? 'null';
        $messageText = "Processing of message #$messageId is stopped because of an exception:\n$error.";

        parent::__construct($messageText, 0, $previous);
    }

    public function getQueueMessage(): MessageInterface
    {
        return $this->queueMessage;
    }
}
