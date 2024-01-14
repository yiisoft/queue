<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\Consume\Support;

use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Middleware\Consume\MessageHandlerConsumeInterface;
use Yiisoft\Queue\Middleware\Consume\MiddlewareConsumeInterface;
use Yiisoft\Queue\Middleware\Consume\ConsumeRequest;

final class TestMiddleware implements MiddlewareConsumeInterface
{
    public function __construct(private string $message = 'New middleware test data')
    {
    }

    public function processConsume(ConsumeRequest $request, MessageHandlerConsumeInterface $handler): ConsumeRequest
    {
        return $request->withMessage(new Message($this->message));
    }
}
