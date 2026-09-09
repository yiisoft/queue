<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Integration\Support;

use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Middleware\Consume\ConsumeRequest;
use Yiisoft\Queue\Middleware\Consume\ConsumeHandlerInterface;
use Yiisoft\Queue\Middleware\Consume\ConsumeMiddlewareInterface;
use Yiisoft\Queue\Middleware\Push\PushHandlerInterface;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareInterface;
use Yiisoft\Queue\Middleware\Push\PushRequest;

final class TestMiddleware implements PushMiddlewareInterface, ConsumeMiddlewareInterface
{
    public function __construct(private readonly string $stage) {}

    public function processPush(PushRequest $request, PushHandlerInterface $handler): PushRequest
    {
        $message = $request->getMessage();
        /** @var array<int, array<int, mixed>|scalar|null> $stack */
        $stack = $message->getPayload();
        $stack[] = $this->stage;

        return $handler->handlePush($request->withMessage(new GenericMessage($message->getType(), $stack)));
    }

    public function processConsume(ConsumeRequest $request, ConsumeHandlerInterface $handler): ConsumeRequest
    {
        $message = $request->getMessage();
        /** @var array<int, array<int, mixed>|scalar|null> $stack */
        $stack = $message->getPayload();
        $stack[] = $this->stage;
        $messageNew = new GenericMessage($message->getType(), $stack);

        return $handler->handleConsume($request->withMessage($messageNew));
    }
}
