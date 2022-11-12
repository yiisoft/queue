<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Middleware\Implementation\FailureStrategy\Dispatcher;

use Throwable;
use Yiisoft\Yii\Queue\Middleware\Consume\ConsumeRequest;

/**
 * A failure strategy pipeline. It organizes a strategy list into an executable pipeline.
 */
interface PipelineInterface
{
    /**
     * Handle failed request through Failure Strategies pipeline
     *
     * @param ConsumeRequest $request The failed request
     * @param Throwable $exception An exception thrown while the message was processed
     *
     * @return ConsumeRequest Modified consume request
     */
    public function handle(ConsumeRequest $request, Throwable $exception): ConsumeRequest;
}
