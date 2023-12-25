<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Support;

use Yiisoft\Yii\Queue\Message\MessageHandlerInterface;
use Yiisoft\Yii\Queue\Message\MessageInterface;

class NullMessageHandler implements MessageHandlerInterface
{
    public function handle(MessageInterface $message): void
    {
    }
}
