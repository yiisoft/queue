<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message;

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

    public function getHandlerName(): string
    {
        return $this->message->getHandlerName();
    }

    public function getData(): mixed
    {
        return $this->message->getData();
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

    /**
     * Finds an envelope in the current envelope stack or creates a new one from the message.
     *
     * @template T
     *
     * @psalm-param T<class-string<EnvelopeInterface>> $className
     * @throws NotEnvelopInterfaceException is thrown if the given class does not implement {@see EnvelopeInterface}.
     *
     * @psalm-return T
     */
    public function getEnvelopeFromStack(string $className): EnvelopeInterface
    {
        if (!is_a($className, EnvelopeInterface::class, true)) {
            throw new NotEnvelopInterfaceException($className);
        }

        if (get_class($this) === $className) {
            return $this;
        }

        if ($this->message instanceof EnvelopeInterface) {
            return $this->message->getEnvelopeFromStack($className);
        }

        return $className::fromMessage($this->message);
    }

    public static function getEnvelopeFromMessage(MessageInterface $message): EnvelopeInterface
    {
        if ($message instanceof EnvelopeInterface) {
            return $message->getEnvelopeFromStack(static::class);
        }

        return static::fromMessage($message);
    }

    public function getEnvelopeMetadata(): array
    {
        return [];
    }
}
