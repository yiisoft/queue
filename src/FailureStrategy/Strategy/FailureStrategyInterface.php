<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\FailureStrategy\Strategy;

use Yiisoft\Yii\Queue\FailureStrategy\Dispatcher\PipelineInterface;
use Yiisoft\Yii\Queue\Message\MessageInterface;

interface FailureStrategyInterface
{
    public function handle(MessageInterface $message, ?PipelineInterface $pipeline): bool;
}
