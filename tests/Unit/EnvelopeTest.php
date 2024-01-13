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
    }

    public function testEnvelopeDuplicates(): void
    {
        $message = new Message('handler', 'test');
        $message = new IdEnvelope($message, 'test-id');
        $message = new IdEnvelope($message, 'test-id');
        $message = new IdEnvelope($message, 'test-id');

        $this->assertEquals('test', $message->getMessage()->getData());

        $stack = $message->getMetadata()[EnvelopeInterface::ENVELOPE_STACK_KEY];
        $this->assertIsArray($stack);

        $this->assertEquals([
            IdEnvelope::class,
            IdEnvelope::class,
            IdEnvelope::class,
        ], $stack);
    }
}
