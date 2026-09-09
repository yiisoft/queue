<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\Push;

use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Middleware\Push\PushRequest;

final class PushRequestTest extends TestCase
{
    public function testQueueNameIsNormalizedAndRequestIsImmutable(): void
    {
        $message = new GenericMessage('test', 'payload');
        $request = new PushRequest($message, '42');
        $copy = $request->withMessage(new GenericMessage('other', null));

        self::assertSame('42', $request->getQueueName());
        self::assertSame($message, $request->getMessage());
        self::assertNotSame($request, $copy);
        self::assertSame('42', $copy->getQueueName());
        self::assertFalse(method_exists($request, 'withQueueName'));
    }
}
