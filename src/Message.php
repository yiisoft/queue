<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue;

use Yiisoft\Yii\Queue\Payload\PayloadInterface;

class Message implements MessageInterface
{
    private string $id;
    private PayloadInterface $payload;

    public function __construct(string $id, PayloadInterface $payload)
    {
        $this->id = $id;
        $this->payload = $payload;
    }

    /**
     * @inheritDoc
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @inheritDoc
     */
    public function getPayload(): PayloadInterface
    {
        return $this->payload;
    }
}
