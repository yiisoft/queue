<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Message\IdEnvelope;
use Yiisoft\Queue\Message\SimpleMessage;

final class EnvelopeTest extends TestCase
{
    public function testEnvelopeStack(): void
    {
        $message = new SimpleMessage('handler', 'test');
        $message = new IdEnvelope($message, 'test-id');

        $this->assertEquals('test', $message->getMessage()->getData());
        $this->assertEquals('test-id', $message->getMetadata()[IdEnvelope::MESSAGE_ID_KEY]);
    }

    public function testEnvelopeDuplicates(): void
    {
        $message = new SimpleMessage('handler', 'test');
        $message = new IdEnvelope($message, 'test-id-1');
        $message = new IdEnvelope($message, 'test-id-2');
        $message = new IdEnvelope($message, 'test-id-3');

        $this->assertEquals('test', $message->getMessage()->getData());
        $this->assertEquals('test-id-3', $message->getMetadata()[IdEnvelope::MESSAGE_ID_KEY]);
    }
}
