<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message;

/**
 * ID envelope allows to identify a message.
 */
final class IdEnvelope implements EnvelopeInterface
{
    use EnvelopeTrait;

    public const MESSAGE_ID_KEY = 'yii-message-id';

    public function __construct(
        private MessageInterface $message,
        private string|int|null $id = null,
    ) {
    }

    public static function fromMessage(MessageInterface $message): self
    {
        return new self($message, self::getIdFromMessage($message));
    }

    public function setId(string|int|null $id): void
    {
        $this->id = $id;
    }

    public function getId(): string|int|null
    {
        return $this->id ?? self::getIdFromMessage($this->message);
    }

    private function getEnvelopeMetadata(): array
    {
        return [self::MESSAGE_ID_KEY => $this->getId()];
    }

    private static function getIdFromMessage(MessageInterface $message): string|int|null
    {
        $id = $message->getMetadata()[self::MESSAGE_ID_KEY] ?? null;
        if ($id instanceof \Stringable) {
            $id = (string) $id;
        }

        // We don't throw an error as this value could come from external sources,
        // and we should process the message either way
        if (!is_string($id) && !is_int($id)) {
            return null;
        }

        return $id;
    }
}
