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
        $meta = ['attempt' => 1, 'error' => 'Test error'];

        $envelope = new FailureEnvelope($message, $meta);

        $this->assertSame($message, $envelope->getMessage());
        $this->assertArrayHasKey(FailureEnvelope::META_FAILURE, $envelope->getMeta());
        $this->assertSame($meta, $envelope->getMeta()[FailureEnvelope::META_FAILURE]);
    }

    public function testFromMessageWithExistingMeta(): void
    {
        $existingMeta = ['attempt' => 1];
        $message = $this->createMessage([FailureEnvelope::META_FAILURE => $existingMeta]);

        $envelope = FailureEnvelope::fromMessage($message);

        $this->assertSame($existingMeta, $envelope->getMeta()[FailureEnvelope::META_FAILURE]);
    }

    public function testFromMessageWithoutMeta(): void
    {
        $message = $this->createMessage();

        $envelope = FailureEnvelope::fromMessage($message);

        $this->assertArrayHasKey(FailureEnvelope::META_FAILURE, $envelope->getMeta());
        $this->assertSame([], $envelope->getMeta()[FailureEnvelope::META_FAILURE]);
    }

    public function testMetaMerging(): void
    {
        $existingMeta = ['attempt' => 1, 'firstError' => 'First error'];
        $message = $this->createMessage([FailureEnvelope::META_FAILURE => $existingMeta]);
        $newMeta = ['attempt' => 2, 'lastError' => 'Last error'];

        $envelope = new FailureEnvelope($message, $newMeta);

        $mergedMeta = $envelope->getMeta()[FailureEnvelope::META_FAILURE];
        $this->assertSame(2, $mergedMeta['attempt']);
        $this->assertSame('First error', $mergedMeta['firstError']);
        $this->assertSame('Last error', $mergedMeta['lastError']);
    }

    private function createMessage(array $meta = []): MessageInterface
    {
        return (new GenericMessage('test-handler', ['test-data']))->withMeta($meta);
    }
}
