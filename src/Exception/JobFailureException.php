<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Exception;

use RuntimeException;
use Throwable;
use Yiisoft\Queue\Message\MessageInterface;

class JobFailureException extends RuntimeException
{
    public function __construct(private MessageInterface $queueMessage, Throwable $previous)
    {
        $error = $previous->getMessage();
        $messageId = $queueMessage->getId() ?? 'null';
        $messageText = "Processing of message #$messageId is stopped because of an exception:\n$error.";

        parent::__construct($messageText, 0, $previous);
    }

    public function getQueueMessage(): MessageInterface
    {
        return $this->queueMessage;
    }
}
