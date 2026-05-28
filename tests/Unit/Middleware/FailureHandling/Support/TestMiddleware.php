<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\FailureHandling\Support;

use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Middleware\FailureHandling\FailureHandlingRequest;
use Yiisoft\Queue\Middleware\FailureHandling\FailureHandlerInterface;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareInterface;

final class TestMiddleware implements FailureMiddlewareInterface
{
    public function __construct(private readonly string $message = 'New middleware test data') {}

    public function processFailure(FailureHandlingRequest $request, FailureHandlerInterface $handler): FailureHandlingRequest
    {
        return $request->withMessage(new GenericMessage('test', $this->message));
    }
}
