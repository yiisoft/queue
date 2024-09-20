<?php

declare(strict_types=1);

namespace Unit;

use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Message\EnvelopeInterface;
use Yiisoft\Queue\Message\IdEnvelope;
use Yiisoft\Queue\Message\Message;

final class EnvelopeTest extends TestCase
{
    public function testEnvelopeStack(): void
    {
        $message = new Message('handler', 'test');
        $message = new IdEnvelope($message, 'test-id');

        $this->assertEquals('test', $message->getMessage()->getData());

        $stack = $message->getMetadata()[EnvelopeInterface::ENVELOPE_STACK_KEY];
        $this->assertIsArray($stack);

        $this->assertEquals([
            IdEnvelope::class,
        ], $stack);

        $this->assertEquals('test-id', $message->getMetadata()[IdEnvelope::MESSAGE_ID_KEY]);
    }

    public function testEnvelopeDuplicates(): void
    {
        $message = new Message('handler', 'test');
        $message = new IdEnvelope($message, 'test-id-1');
        $message = new IdEnvelope($message, 'test-id-2');
        $message = new IdEnvelope($message, 'test-id-3');

        $this->assertEquals('test', $message->getMessage()->getData());

        $stack = $message->getMetadata()[EnvelopeInterface::ENVELOPE_STACK_KEY];
        $this->assertIsArray($stack);
        $this->assertEquals([IdEnvelope::class], $stack);

        $this->assertEquals('test-id-3', $message->getMetadata()[IdEnvelope::MESSAGE_ID_KEY]);
    }
}
