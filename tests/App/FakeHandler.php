<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\App;

use Yiisoft\Queue\Message\MessageInterface;
use RuntimeException;

final class FakeHandler
{
    public static array $processedMessages = [];

    public function __construct()
    {
        self::$processedMessages = [];
    }

    public function __invoke(MessageInterface $message)
    {
        self::$processedMessages[] = $message;
    }

    public function execute(MessageInterface $message): void
    {
        self::$processedMessages[] = $message;
    }

    public static function staticExecute(MessageInterface $message): void
    {
        self::$processedMessages[] = $message;
    }

    public function executeWithException(MessageInterface $message): never
    {
        throw new RuntimeException('Test exception');
    }
}
