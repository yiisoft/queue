<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Middleware\Consume;

use Yiisoft\Yii\Queue\Adapter\AdapterInterface;
use Yiisoft\Yii\Queue\Message\MessageInterface;

interface MiddlewareConsumeInterface
{
    public function processConsume(MessageInterface $message, AdapterInterface $adapter): void;
}
