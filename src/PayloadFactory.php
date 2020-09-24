<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue;

use Yiisoft\Yii\Queue\Message\Message;
use Yiisoft\Yii\Queue\Message\MessageInterface;
use Yiisoft\Yii\Queue\Payload\AttemptsRestrictedPayloadInterface;
use Yiisoft\Yii\Queue\Payload\BasicPayload;
use Yiisoft\Yii\Queue\Payload\DelayablePayloadInterface;
use Yiisoft\Yii\Queue\Payload\PayloadInterface;
use Yiisoft\Yii\Queue\Payload\PrioritisedPayloadInterface;

class PayloadFactory
{
    public function createMessage(PayloadInterface $payload): MessageInterface
    {
        $meta = $payload->getMeta();

        if ($payload instanceof DelayablePayloadInterface) {
            $meta[PayloadInterface::META_KEY_DELAY] = $payload->getDelay();
        }

        if ($payload instanceof PrioritisedPayloadInterface) {
            $meta[PayloadInterface::META_KEY_PRIORITY] = $payload->getPriority();
        }

        if ($payload instanceof AttemptsRestrictedPayloadInterface) {
            $meta[PayloadInterface::META_KEY_ATTEMPTS] = $payload->getAttempts();
        }

        return new Message($payload->getName(), $payload->getData(), $meta);
    }

    public function createPayload(MessageInterface $message, array $metaOverwrite): PayloadInterface
    {
        $metaOriginal = $message->getPayloadMeta();
        if (isset($metaOriginal[PayloadInterface::META_KEY_DELAY])) {
            unset($metaOriginal[PayloadInterface::META_KEY_DELAY]);
        }

        $meta = array_merge($metaOriginal, $metaOverwrite);

        return new BasicPayload($message->getPayloadName(), $message->getPayloadData(), $meta);
    }
}
