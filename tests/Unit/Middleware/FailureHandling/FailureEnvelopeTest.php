<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\FailureHandling;

use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\FailureHandling\FailureEnvelope;

final class FailureEnvelopeTest extends TestCase
{
    public function testConstructor(): void
    {
        $message = $this->createMessage();
        $metadata = ['attempt' => 1, 'error' => 'Test error'];

        $envelope = new FailureEnvelope($message, $metadata);

        $this->assertSame($message, $envelope->getMessage());
        $this->assertArrayHasKey(FailureEnvelope::FAILURE_META_KEY, $envelope->getMetadata());
        $this->assertSame($metadata, $envelope->getMetadata()[FailureEnvelope::FAILURE_META_KEY]);
    }

    public function testFromMessageWithExistingMetadata(): void
    {
        $existingMetadata = ['attempt' => 1];
        $message = $this->createMessage([FailureEnvelope::FAILURE_META_KEY => $existingMetadata]);

        $envelope = FailureEnvelope::fromMessage($message);

        $this->assertSame($existingMetadata, $envelope->getMetadata()[FailureEnvelope::FAILURE_META_KEY]);
    }

    public function testFromMessageWithoutMetadata(): void
    {
        $message = $this->createMessage();

        $envelope = FailureEnvelope::fromMessage($message);

        $this->assertArrayHasKey(FailureEnvelope::FAILURE_META_KEY, $envelope->getMetadata());
        $this->assertSame([], $envelope->getMetadata()[FailureEnvelope::FAILURE_META_KEY]);
    }

    public function testMetadataMerging(): void
    {
        $existingMetadata = ['attempt' => 1, 'firstError' => 'First error'];
        $message = $this->createMessage([FailureEnvelope::FAILURE_META_KEY => $existingMetadata]);
        $newMetadata = ['attempt' => 2, 'lastError' => 'Last error'];

        $envelope = new FailureEnvelope($message, $newMetadata);

        $mergedMetadata = $envelope->getMetadata()[FailureEnvelope::FAILURE_META_KEY];
        $this->assertSame(2, $mergedMetadata['attempt']);
        $this->assertSame('First error', $mergedMetadata['firstError']);
        $this->assertSame('Last error', $mergedMetadata['lastError']);
    }

    public function testFromData(): void
    {
        $handlerName = 'test-handler';
        $data = ['key' => 'value'];
        $metadata = [
            'meta' => 'data',
            FailureEnvelope::FAILURE_META_KEY => ['attempt' => 1],
        ];

        $envelope = FailureEnvelope::fromData($handlerName, $data, $metadata);

        $this->assertInstanceOf(FailureEnvelope::class, $envelope);
        $this->assertSame($handlerName, $envelope->getHandlerName());
        $this->assertSame($data, $envelope->getData());
        $this->assertArrayHasKey('meta', $envelope->getMetadata());
        $this->assertSame('data', $envelope->getMetadata()['meta']);
        $this->assertSame(['attempt' => 1], $envelope->getMetadata()[FailureEnvelope::FAILURE_META_KEY]);
    }

    private function createMessage(array $metadata = []): MessageInterface
    {
        return new Message('test-handler', ['test-data'], $metadata);
    }
}
