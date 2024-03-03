<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Integration\Support;

use Yiisoft\Queue\Middleware\MessageHandlerInterface;
use Yiisoft\Queue\Middleware\MiddlewareInterface;
use Yiisoft\Queue\Middleware\Request;

final class TestMiddleware implements MiddlewareInterface
{
    public function __construct(private string $stage)
    {
    }

    public function process(Request $request, MessageHandlerInterface $handler): Request
    {
        $message = $request->getMessage();
        $stack = $message->getData();
        $stack[] = $this->stage;
        $messageNew = $message->withData($stack);

        return $handler->handle($request->withMessage($messageNew));
    }

    public function process2(Request $request, MessageHandlerInterface $handler): Request
    {
        $message = $request->getMessage();
        $stack = $message->getData();
        $stack[] = $this->stage;
        $messageNew = $message->withData($stack);

        return $handler->handle($request->withMessage($messageNew));
    }
}
