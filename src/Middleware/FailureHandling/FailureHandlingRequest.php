<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\FailureHandling;

use Throwable;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\Request;
use Yiisoft\Queue\QueueInterface;

// TODO: remove class
final class FailureHandlingRequest extends Request
{
    public function __construct(private MessageInterface $message, private ?Throwable $exception)
    {
        parent::__construct($message, null);
    }

    public function getException(): ?Throwable
    {
        return $this->exception;
    }
}
