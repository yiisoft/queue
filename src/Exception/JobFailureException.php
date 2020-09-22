<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Exception;

use RuntimeException;
use Throwable;
use Yiisoft\Yii\Queue\Message\MessageInterface;

class JobFailureException extends RuntimeException
{
    private MessageInterface $queueMessage;

    public function __construct(MessageInterface $message, Throwable $previous)
    {
        $this->queueMessage = $message;

        $error = $previous->getMessage();
        $messageId = $message->getId() ?? 'null';
        $messageText = "Processing of message #$messageId is stopped because of an exception:\n$error.";

        parent::__construct($messageText, 0, $previous);
    }

    public function getQueueMessage(): MessageInterface
    {
        return $this->queueMessage;
    }
}
