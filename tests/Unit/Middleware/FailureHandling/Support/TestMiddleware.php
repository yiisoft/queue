<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\FailureHandling\Support;

use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Middleware\FailureHandling\FailureHandlingRequest;
use Yiisoft\Queue\Middleware\FailureHandling\MessageFailureHandlerInterface;
use Yiisoft\Queue\Middleware\FailureHandling\MiddlewareFailureInterface;

final class TestMiddleware implements MiddlewareFailureInterface
{
    public function __construct(private string $message = 'New middleware test data')
    {
    }

    public function processFailure(FailureHandlingRequest $request, MessageFailureHandlerInterface $handler): FailureHandlingRequest
    {
        return $request->withMessage(new Message($this->message));
    }
}
