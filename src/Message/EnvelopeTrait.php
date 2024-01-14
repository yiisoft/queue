<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message;

use Yiisoft\Queue\QueueInterface;

trait EnvelopeTrait
{
    private MessageInterface $message;

    public function getMessage(): MessageInterface
    {
        return $this->message;
    }

    public function withMessage(MessageInterface $message): self
    {
        $instance = clone $this;
        $instance->message = $message;

        return $instance;
    }

    /**
     * @return class-string<MessageHandlerInterface>
     */
    public function getHandler(): string
    {
        return $this->message->getHandler();
    }

    public function getData(): mixed
    {
        return $this->message->getData();
    }

    public static function fromMessage(MessageInterface $message): self
    {
        return new static($message);
    }

    public function getMetadata(): array
    {
        return array_merge(
            $this->message->getMetadata(),
            [
                self::ENVELOPE_STACK_KEY => array_merge(
                    $this->message->getMetadata()[self::ENVELOPE_STACK_KEY] ?? [],
                    [self::class],
                ),
            ],
            $this->getEnvelopeMetadata(),
        );
    }

    public function getEnvelopeMetadata(): array
    {
        return [];
    }

    public function withData(mixed $data): self
    {
        $instance = clone $this;
        $instance->message = $instance->message->withData($data);

        return $instance;
    }

    public function withMetadata(array $metadata): self
    {
        $instance = clone $this;
        $instance->message = $instance->message->withMetadata($metadata);

        return $instance;
    }

    public function withQueue(QueueInterface $queue): self
    {
        $instance = clone $this;
        $instance->message = $instance->message->withQueue($queue);

        return $instance;
    }
}
