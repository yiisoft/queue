<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\FailureStrategy\Dispatcher;

use Yiisoft\Yii\Queue\Message\MessageInterface;

interface PipelineInterface
{
    public function handle(MessageInterface $message): bool;
}
