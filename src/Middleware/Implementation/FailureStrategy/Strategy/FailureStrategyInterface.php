<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Middleware\Implementation\FailureStrategy\Strategy;

use Throwable;
use Yiisoft\Yii\Queue\Middleware\Consume\ConsumeRequest;
use Yiisoft\Yii\Queue\Middleware\Implementation\FailureStrategy\Dispatcher\PipelineInterface;

interface FailureStrategyInterface
{
    public function handle(ConsumeRequest $request, Throwable $exception, PipelineInterface $pipeline): ConsumeRequest;
}
