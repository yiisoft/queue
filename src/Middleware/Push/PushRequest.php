<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Push;

use Yiisoft\Queue\Message\MessageInterface;

final class PushRequest
{
    public function __construct(
        private MessageInterface $message,
    ) {}

    public function getMessage(): MessageInterface
    {
        return $this->message;
    }

    public function withMessage(MessageInterface $message): self
    {
        $new = clone $this;
        $new->message = $message;
        return $new;
    }
}
