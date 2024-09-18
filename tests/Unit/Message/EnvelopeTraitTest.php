<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Message;

use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Message\EnvelopeInterface;
use Yiisoft\Queue\Message\IdEnvelope;
use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Message\NotEnvelopInterfaceException;
use Yiisoft\Queue\Middleware\FailureHandling\FailureEnvelope;

class EnvelopeTraitTest extends TestCase
{
    public function testGetEnvelopeFromStack(): void
    {
        $message = new Message('handler', 'data');
        $envelope = new FailureEnvelope(new IdEnvelope($message, 'id value'), ['fail' => true]);
        $result = $envelope->getEnvelopeFromStack(IdEnvelope::class);

        $this->assertInstanceOf(IdEnvelope::class, $result);
        $this->assertEquals('id value', $result->getId());
    }

    public function testGetEnvelopeFromStackNotInStack(): void
    {
        $message = new Message('handler', 'data', [IdEnvelope::MESSAGE_ID_KEY => 'id value']);
        $envelope = new FailureEnvelope($message, ['fail' => true]);
        $result = $envelope->getEnvelopeFromStack(IdEnvelope::class);

        $this->assertInstanceOf(IdEnvelope::class, $result);
        $this->assertEquals('id value', $result->getId());
    }

    public function testGetEnvelopeFromStackNotEnvelope(): void
    {
        $this->expectException(NotEnvelopInterfaceException::class);
        $interface = EnvelopeInterface::class;
        $this->expectExceptionMessage("The given class \"foo\" does not implement \"$interface\".");

        $message = new Message('handler', 'data', [IdEnvelope::MESSAGE_ID_KEY => 'id value']);
        $envelope = new FailureEnvelope($message, ['fail' => true]);
        $envelope->getEnvelopeFromStack('foo');
    }

    public function testGetEnvelopeFromMessage(): void
    {
        $message = new Message('handler', 'data');
        $envelope = new FailureEnvelope(new IdEnvelope($message, 'id value'), ['fail' => true]);
        $result = IdEnvelope::getEnvelopeFromMessage($envelope);

        $this->assertInstanceOf(IdEnvelope::class, $result);
        $this->assertEquals('id value', $result->getId());
    }

    public function testGetEnvelopeFromMessageNotInStack(): void
    {
        $message = new Message('handler', 'data', [IdEnvelope::MESSAGE_ID_KEY => 'id value']);
        $result = IdEnvelope::getEnvelopeFromMessage($message);

        $this->assertInstanceOf(IdEnvelope::class, $result);
        $this->assertEquals('id value', $result->getId());
    }
}
