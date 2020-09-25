<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Payload;

use Yiisoft\Yii\Queue\Message\MessageInterface;

interface PayloadFactoryInterface
{
    public function createMessage(PayloadInterface $payload): MessageInterface;

    public function createPayload(MessageInterface $message, array $metaOverwrite): PayloadInterface;
}
