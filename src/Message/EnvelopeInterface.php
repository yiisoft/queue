<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Message;

interface EnvelopeInterface extends MessageInterface
{
    public function getMessage(): MessageInterface;

    public function withMessage(MessageInterface $message): self;
}
