<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Middleware\Implementation\FailureStrategy\Dispatcher;

use Throwable;
use Yiisoft\Yii\Queue\Middleware\Consume\ConsumeRequest;

interface PipelineInterface
{
    public function handle(ConsumeRequest $request, Throwable $exception): ConsumeRequest;
}
