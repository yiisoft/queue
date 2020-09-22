<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\FailureStrategy;

use Yiisoft\Yii\Queue\Message\MessageInterface;

interface FailureStrategyInterface
{
    public function handle(MessageInterface $message, $stack): void;
}
