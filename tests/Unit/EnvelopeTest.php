<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Message\IdEnvelope;
use Yiisoft\Queue\Message\GenericMessage;

final class EnvelopeTest extends TestCase
{
    public function testEnvelopeStack(): void
    {
        $message = new GenericMessage('handler', 'test');
        $message = new IdEnvelope($message, 'test-id');

        $this->assertEquals('test', $message->getMessage()->getPayload());
        $this->assertEquals('test-id', $message->getMeta()[IdEnvelope::META_ID]);
    }

    public function testEnvelopeDuplicates(): void
    {
        $message = new GenericMessage('handler', 'test');
        $message = new IdEnvelope($message, 'test-id-1');
        $message = new IdEnvelope($message, 'test-id-2');
        $message = new IdEnvelope($message, 'test-id-3');

        $this->assertEquals('test', $message->getMessage()->getPayload());
        $this->assertEquals('test-id-3', $message->getMeta()[IdEnvelope::META_ID]);
    }
}
