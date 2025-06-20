<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\Push\Support;

use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Middleware\Push\MessageHandlerPushInterface;
use Yiisoft\Queue\Middleware\Push\MiddlewarePushInterface;
use Yiisoft\Queue\Middleware\Push\PushRequest;

final class TestMiddleware implements MiddlewarePushInterface
{
    public function __construct(private readonly string $message = 'New middleware test data')
    {
    }

    public function processPush(PushRequest $request, MessageHandlerPushInterface $handler): PushRequest
    {
        return $request->withMessage(new Message('test', $this->message));
    }
}
