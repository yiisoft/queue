<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Integration\Support;

use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\Consume\ConsumeRequest;
use Yiisoft\Queue\Middleware\Consume\ConsumeHandlerInterface;
use Yiisoft\Queue\Middleware\Consume\ConsumeMiddlewareInterface;
use Yiisoft\Queue\Middleware\Push\PushHandlerInterface;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareInterface;

final class TestMiddleware implements PushMiddlewareInterface, ConsumeMiddlewareInterface
{
    public function __construct(private readonly string $stage) {}

    public function processPush(MessageInterface $message, PushHandlerInterface $handler): MessageInterface
    {
        $stack = $message->getData();
        $stack[] = $this->stage;

        return $handler->handlePush(new Message($message->getType(), $stack));
    }

    public function processConsume(ConsumeRequest $request, ConsumeHandlerInterface $handler): ConsumeRequest
    {
        $message = $request->getMessage();
        $stack = $message->getData();
        $stack[] = $this->stage;
        $messageNew = new Message($message->getType(), $stack);

        return $handler->handleConsume($request->withMessage($messageNew));
    }
}
