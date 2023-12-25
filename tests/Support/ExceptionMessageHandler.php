<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Support;

use RuntimeException;
use Yiisoft\Yii\Queue\Message\MessageHandlerInterface;
use Yiisoft\Yii\Queue\Message\MessageInterface;

class ExceptionMessageHandler implements MessageHandlerInterface
{
    public function handle(MessageInterface $message): void
    {
        throw new RuntimeException('Test exception');
    }
}
