<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Integration\Support;

use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Middleware\MessageHandlerInterface;
use Yiisoft\Queue\Middleware\MiddlewareInterface;
use Yiisoft\Queue\Middleware\Request;

final class ConsumeMiddleware implements MiddlewareInterface
{
    public function __construct(private string $stage)
    {
    }

    public function process(Request $request, MessageHandlerInterface $handler): Request
    {
        $message = $request->getMessage();
        $stack = $message->getData();
        $stack[] = $this->stage;
        $messageNew = new Message($message->getHandlerName(), $stack);

        return $handler->handle($request->withMessage($messageNew));
    }
}
