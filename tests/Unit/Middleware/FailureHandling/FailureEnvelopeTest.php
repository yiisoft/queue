<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\FailureHandling;

use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Message\GenericMessage;
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
        $this->assertArrayHasKey(FailureEnvelope::META_FAILURE_METADATA, $envelope->getMetadata());
        $this->assertSame($metadata, $envelope->getMetadata()[FailureEnvelope::META_FAILURE_METADATA]);
    }

    public function testFromMessageWithExistingMetadata(): void
    {
        $existingMetadata = ['attempt' => 1];
        $message = $this->createMessage([FailureEnvelope::META_FAILURE_METADATA => $existingMetadata]);

        $envelope = FailureEnvelope::fromMessage($message);

        $this->assertSame($existingMetadata, $envelope->getMetadata()[FailureEnvelope::META_FAILURE_METADATA]);
    }

    public function testFromMessageWithoutMetadata(): void
    {
        $message = $this->createMessage();

        $envelope = FailureEnvelope::fromMessage($message);

        $this->assertArrayHasKey(FailureEnvelope::META_FAILURE_METADATA, $envelope->getMetadata());
        $this->assertSame([], $envelope->getMetadata()[FailureEnvelope::META_FAILURE_METADATA]);
    }

    public function testMetadataMerging(): void
    {
        $existingMetadata = ['attempt' => 1, 'firstError' => 'First error'];
        $message = $this->createMessage([FailureEnvelope::META_FAILURE_METADATA => $existingMetadata]);
        $newMetadata = ['attempt' => 2, 'lastError' => 'Last error'];

        $envelope = new FailureEnvelope($message, $newMetadata);

        $mergedMetadata = $envelope->getMetadata()[FailureEnvelope::META_FAILURE_METADATA];
        $this->assertSame(2, $mergedMetadata['attempt']);
        $this->assertSame('First error', $mergedMetadata['firstError']);
        $this->assertSame('Last error', $mergedMetadata['lastError']);
    }

    private function createMessage(array $metadata = []): MessageInterface
    {
        return (new GenericMessage('test-handler', ['test-data']))->withMetadata($metadata);
    }
}
