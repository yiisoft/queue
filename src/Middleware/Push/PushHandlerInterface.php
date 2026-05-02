<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Push;

use Yiisoft\Queue\Message\MessageInterface;

interface PushHandlerInterface
{
    public function handlePush(MessageInterface $message): MessageInterface;
}
