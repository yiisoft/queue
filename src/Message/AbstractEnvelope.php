<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message;

use Yiisoft\Arrays\ArrayHelper;

abstract class AbstractEnvelope implements EnvelopeInterface
{
    protected array $metadata = [];
    private MessageInterface $message;

    public function __construct(MessageInterface $message)
    {
        $this->metadata = $message->getMetadata();
        $envelopes = [static::class];
        while ($message instanceof EnvelopeInterface) {
            if ($message::class !== static::class) {
                $envelopes = [$message::class];
            }

            $message = $message->getMessage();
        }
        $this->message = $message;

        if (isset($this->metadata[EnvelopeInterface::ENVELOPE_STACK_KEY]) && is_array($this->metadata[EnvelopeInterface::ENVELOPE_STACK_KEY])) {
            $this->metadata[EnvelopeInterface::ENVELOPE_STACK_KEY] = array_merge(
                $envelopes,
                array_filter(
                    $this->metadata[EnvelopeInterface::ENVELOPE_STACK_KEY],
                    static fn (string $envelope): bool => !in_array($envelope, $envelopes),
                ),
            );
        } else {
            $this->metadata[EnvelopeInterface::ENVELOPE_STACK_KEY] = [static::class];
        }
    }

    public function getMessage(): MessageInterface
    {
        return $this->message;
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
        return ArrayHelper::merge(
            $this->metadata,
            $this->getEnvelopeMetadata(),
        );
    }

    public static function fromData(string $handlerName, mixed $data, array $metadata = []): MessageInterface
    {
        return static::fromMessage(Message::fromData($handlerName, $data, $metadata));
    }

    /**
     * Metadata of the envelope
     *
     * @return array
     */
    abstract protected function getEnvelopeMetadata(): array;
}
