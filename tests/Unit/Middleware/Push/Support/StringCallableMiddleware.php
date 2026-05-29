<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\Push\Support;

use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Message\MessageInterface;

final class StringCallableMiddleware
{
    public static function handle(MessageInterface $message): MessageInterface
    {
        return new GenericMessage('test', 'String callable data');
    }
}
