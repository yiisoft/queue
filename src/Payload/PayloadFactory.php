<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Payload;

use Yiisoft\Yii\Queue\Message\Message;
use Yiisoft\Yii\Queue\Message\MessageInterface;

class PayloadFactory implements PayloadFactoryInterface
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
        unset($metaOriginal[PayloadInterface::META_KEY_DELAY]);

        $meta = array_merge($metaOriginal, $metaOverwrite);

        return new BasicPayload($message->getPayloadName(), $message->getPayloadData(), $meta);
    }
}
