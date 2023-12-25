<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Support;

use Yiisoft\Yii\Queue\Message\MessageHandlerInterface;
use Yiisoft\Yii\Queue\Message\MessageInterface;

class StackMessageHandler implements MessageHandlerInterface
{
    public array $processedMessages = [];

    public function handle(MessageInterface $message): void
    {
        $this->processedMessages[] = $message;
    }
}
