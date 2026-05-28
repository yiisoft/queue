<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\Consume\Support;

use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Middleware\Consume\ConsumeHandlerInterface;
use Yiisoft\Queue\Middleware\Consume\ConsumeMiddlewareInterface;
use Yiisoft\Queue\Middleware\Consume\ConsumeRequest;

final class TestMiddleware implements ConsumeMiddlewareInterface
{
    public function __construct(private readonly string $message = 'New middleware test data') {}

    public function processConsume(ConsumeRequest $request, ConsumeHandlerInterface $handler): ConsumeRequest
    {
        return $request->withMessage(new GenericMessage('test', $this->message));
    }
}
