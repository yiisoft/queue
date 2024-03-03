<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Support;

use Yiisoft\Queue\Message\MessageHandlerInterface;
use Yiisoft\Queue\Message\MessageInterface;

class NullMessageHandler implements MessageHandlerInterface
{
    public function handle(MessageInterface $message): void
    {
    }
}
