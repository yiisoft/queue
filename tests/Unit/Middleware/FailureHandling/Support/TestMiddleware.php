<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Unit\Middleware\FailureHandling\Support;

use Yiisoft\Yii\Queue\Message\Message;
use Yiisoft\Yii\Queue\Middleware\FailureHandling\FailureHandlingRequest;
use Yiisoft\Yii\Queue\Middleware\FailureHandling\MessageFailureHandlerInterface;
use Yiisoft\Yii\Queue\Middleware\FailureHandling\MiddlewareFailureInterface;

final class TestMiddleware implements MiddlewareFailureInterface
{
    public function __construct(private string $message = 'New middleware test data')
    {
    }

    public function processFailure(FailureHandlingRequest $request, MessageFailureHandlerInterface $handler): FailureHandlingRequest
    {
        return $request->withMessage(new Message('test', $this->message));
    }
}
