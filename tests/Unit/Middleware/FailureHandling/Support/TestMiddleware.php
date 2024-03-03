<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\FailureHandling\Support;

use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Middleware\MessageHandlerInterface;
use Yiisoft\Queue\Middleware\MiddlewareInterface;
use Yiisoft\Queue\Middleware\Request;

final class TestMiddleware implements MiddlewareInterface
{
    public function __construct(private string $message = 'New middleware test data')
    {
    }

    public function process(Request $request, MessageHandlerInterface $handler): Request
    {
        return $request->withMessage(new Message($this->message));
    }
}
