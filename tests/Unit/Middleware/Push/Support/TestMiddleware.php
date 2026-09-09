<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\Push\Support;

use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Middleware\Push\PushRequest;
use Yiisoft\Queue\Middleware\Push\PushHandlerInterface;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareInterface;

final class TestMiddleware implements PushMiddlewareInterface
{
    public function __construct(private readonly string $message = 'New middleware test data') {}

    public function processPush(PushRequest $request, PushHandlerInterface $handler): PushRequest
    {
        return $request->withMessage(new GenericMessage('test', $this->message));
    }
}
