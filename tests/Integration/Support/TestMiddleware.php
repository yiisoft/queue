<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Integration\Support;

use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\Consume\ConsumeRequest;
use Yiisoft\Queue\Middleware\Consume\MessageHandlerConsumeInterface;
use Yiisoft\Queue\Middleware\Consume\MiddlewareConsumeInterface;
use Yiisoft\Queue\Middleware\Push\MessageHandlerPushInterface;
use Yiisoft\Queue\Middleware\Push\MiddlewarePushInterface;

final class TestMiddleware implements MiddlewarePushInterface, MiddlewareConsumeInterface
{
    public function __construct(private readonly string $stage) {}

    public function processPush(MessageInterface $message, MessageHandlerPushInterface $handler): MessageInterface
    {
        $stack = $message->getData();
        $stack[] = $this->stage;

        return $handler->handlePush(new Message($message->getType(), $stack));
    }

    public function processConsume(ConsumeRequest $request, MessageHandlerConsumeInterface $handler): ConsumeRequest
    {
        $message = $request->getMessage();
        $stack = $message->getData();
        $stack[] = $this->stage;
        $messageNew = new Message($message->getType(), $stack);

        return $handler->handleConsume($request->withMessage($messageNew));
    }
}
