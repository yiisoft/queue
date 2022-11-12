<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Middleware\Implementation\FailureStrategy;

use Throwable;
use Yiisoft\Yii\Queue\Middleware\Consume\ConsumeRequest;
use Yiisoft\Yii\Queue\Middleware\Consume\MessageHandlerConsumeInterface;
use Yiisoft\Yii\Queue\Middleware\Consume\MiddlewareConsumeInterface;
use Yiisoft\Yii\Queue\Middleware\Implementation\FailureStrategy\Dispatcher\PipelineFactoryInterface;

/**
 * A default middleware to enable Failure Strategy mechanics. It will create and call a suitable
 * Failure Strategy pipeline if a message handling fails.
 */
final class FailureStrategyMiddleware implements MiddlewareConsumeInterface
{
    public function __construct(private PipelineFactoryInterface $factory)
    {
    }

    public function processConsume(ConsumeRequest $request, MessageHandlerConsumeInterface $handler): ConsumeRequest
    {
        try {
            return $handler->handleConsume($request);
        } catch (Throwable $exception) {
            return $this->factory->get($request->getQueue()->getChannelName())->handle($request, $exception);
        }
    }
}
