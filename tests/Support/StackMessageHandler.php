<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Support;

use Yiisoft\Queue\Message\MessageHandlerInterface;
use Yiisoft\Queue\Message\MessageInterface;

class StackMessageHandler implements MessageHandlerInterface
{
    public array $processedMessages = [];

    public function handle(MessageInterface $message): void
    {
        $this->processedMessages[] = $message;
    }
}
