<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message;

interface MessageHandlerInterface
{
    public function handle(MessageInterface $message): void;
}
