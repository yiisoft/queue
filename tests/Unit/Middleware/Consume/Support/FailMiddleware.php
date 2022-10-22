<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Unit\Middleware\Consume\Support;

use RuntimeException;
use Yiisoft\Yii\Queue\Middleware\Consume\MessageHandlerConsumeInterface;
use Yiisoft\Yii\Queue\Middleware\Consume\MiddlewareConsumeInterface;
use Yiisoft\Yii\Queue\Middleware\Consume\ConsumeRequest;

final class FailMiddleware implements MiddlewareConsumeInterface
{
    public function processConsume(ConsumeRequest $request, MessageHandlerConsumeInterface $handler): ConsumeRequest
    {
        throw new RuntimeException('Middleware failed.');
    }
}
