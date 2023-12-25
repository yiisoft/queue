<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\App;

use RuntimeException;
use Yiisoft\Yii\Queue\Message\MessageHandlerInterface;
use Yiisoft\Yii\Queue\Message\MessageInterface;

final class FakeHandler implements MessageHandlerInterface
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

    public function executeWithException(MessageInterface $message): void
    {
        throw new RuntimeException('Test exception');
    }

    public function handle(MessageInterface $message): void
    {
        self::$processedMessages[] = $message;
    }
}
