<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Message;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Message\GenericMessage;

final class GenericMessageTest extends TestCase
{
    public function testConstructorThrowsWhenTypeIsEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Message type must be a non-empty string.');

        new GenericMessage('', null);
    }

    public function testFromPayloadThrowsWhenTypeIsEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Message type must be a non-empty string.');

        GenericMessage::fromPayload('', null);
    }
}
