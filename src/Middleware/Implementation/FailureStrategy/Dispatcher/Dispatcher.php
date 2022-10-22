<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Middleware\Implementation\FailureStrategy\Dispatcher;

use Throwable;
use Yiisoft\Yii\Queue\Middleware\Consume\ConsumeRequest;

class Dispatcher implements DispatcherInterface
{
    private PipelineInterface $pipeline;

    public function __construct(PipelineInterface $pipeline)
    {
        $this->pipeline = $pipeline;
    }

    public function handle(ConsumeRequest $request, Throwable $exception): ConsumeRequest
    {
        return $this->pipeline->handle($request, $exception);
    }
}
