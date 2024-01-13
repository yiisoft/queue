<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\Consume\Support;

use RuntimeException;
use Yiisoft\Queue\Middleware\Consume\MessageHandlerConsumeInterface;
use Yiisoft\Queue\Middleware\Consume\MiddlewareConsumeInterface;
use Yiisoft\Queue\Middleware\Consume\ConsumeRequest;

final class FailMiddleware implements MiddlewareConsumeInterface
{
    public function processConsume(ConsumeRequest $request, MessageHandlerConsumeInterface $handler): ConsumeRequest
    {
        throw new RuntimeException('Middleware failed.');
    }
}
