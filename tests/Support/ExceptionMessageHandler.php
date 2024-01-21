<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Support;

use RuntimeException;
use Yiisoft\Queue\Message\MessageHandlerInterface;
use Yiisoft\Queue\Message\MessageInterface;

class ExceptionMessageHandler implements MessageHandlerInterface
{
    public function handle(MessageInterface $message): void
    {
        throw new RuntimeException('Test exception');
    }
}
