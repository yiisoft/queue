<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Integration\Support;

use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Middleware\Consume\ConsumeRequest;
use Yiisoft\Queue\Middleware\Consume\MessageHandlerConsumeInterface;
use Yiisoft\Queue\Middleware\Consume\MiddlewareConsumeInterface;
use Yiisoft\Queue\Middleware\Push\MessageHandlerPushInterface;
use Yiisoft\Queue\Middleware\Push\MiddlewarePushInterface;
use Yiisoft\Queue\Middleware\Push\PushRequest;

final class TestMiddleware implements MiddlewarePushInterface, MiddlewareConsumeInterface
{
    public function __construct(private readonly string $stage) {}

    public function processPush(PushRequest $request, MessageHandlerPushInterface $handler): PushRequest
    {
        $message = $request->getMessage();
        $stack = $message->getData();
        $stack[] = $this->stage;
        $messageNew = new Message($message->getHandlerName(), $stack);

        return $handler->handlePush($request->withMessage($messageNew));
    }

    public function processConsume(ConsumeRequest $request, MessageHandlerConsumeInterface $handler): ConsumeRequest
    {
        $message = $request->getMessage();
        $stack = $message->getData();
        $stack[] = $this->stage;
        $messageNew = new Message($message->getHandlerName(), $stack);

        return $handler->handleConsume($request->withMessage($messageNew));
    }
}
