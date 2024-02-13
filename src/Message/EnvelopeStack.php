<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message;

final class EnvelopeStack
{
    private array $envelopes;
    private int $position = 0;

    public function __construct(EnvelopeInterface ...$envelopes)
    {
        $this->envelopes = array_combine(
            array_map(fn (EnvelopeInterface $envelope) => $envelope::class, $envelopes),
            $envelopes,
        );
    }

    public function current(): EnvelopeInterface
    {
        return $this->envelopes[$this->position];
    }

    public function next(): void
    {
        if (isset($this->envelopes[$this->position + 1])) {
            $this->position++;
        }

        $this->position = 0;
    }

    public function add(EnvelopeInterface $envelope): void
    {
        $this->envelopes[$envelope::class] = $envelope;
    }

    public function has(string $class): bool
    {
        return isset($this->envelopes[$class]);
    }

    public function getEnvelope(string $class): EnvelopeInterface
    {
        return $this->envelopes[$class];
    }

    public function collectMetadata(): array
    {
        $metadata = [
            EnvelopeInterface::ENVELOPE_STACK_KEY => [],
        ];
        foreach ($this->envelopes as $envelope) {
            $metadata = array_merge($metadata, $envelope->getEnvelopeMetadata());
            $metadata[EnvelopeInterface::ENVELOPE_STACK_KEY][] = $envelope::class;
        }

        return $metadata;
    }
}
