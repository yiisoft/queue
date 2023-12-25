<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Exception;

use RuntimeException;
use Throwable;
use Yiisoft\Yii\Queue\Message\MessageInterface;
use Yiisoft\Yii\Queue\Message\ParametrizedMessageInterface;

class JobFailureException extends RuntimeException
{
    public function __construct(private MessageInterface $queueMessage, Throwable $previous)
    {
        $error = $previous->getMessage();
        $messageId = $queueMessage instanceof ParametrizedMessageInterface ? ($queueMessage->getId() ?? 'null') : 'null';
        $messageText = "Processing of message #$messageId is stopped because of an exception:\n$error.";

        parent::__construct($messageText, 0, $previous);
    }

    public function getQueueMessage(): MessageInterface
    {
        return $this->queueMessage;
    }
}
