<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Integration;

use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;
use Yiisoft\Injector\Injector;
use Yiisoft\Yii\Queue\Message\Message;
use Yiisoft\Yii\Queue\Message\MessageInterface;
use Yiisoft\Yii\Queue\Middleware\Consume\ConsumeMiddlewareDispatcher;
use Yiisoft\Yii\Queue\Middleware\Consume\MiddlewareFactoryConsumeInterface;
use Yiisoft\Yii\Queue\Middleware\FailureHandling\FailureMiddlewareDispatcher;
use Yiisoft\Yii\Queue\Middleware\FailureHandling\MiddlewareFactoryFailureInterface;
use Yiisoft\Yii\Queue\Tests\TestCase;
use Yiisoft\Yii\Queue\Worker\Worker;

final class MessageConsumingTest extends TestCase
{
    private array $messagesProcessed;
    public function testMessagesConsumed(): void
    {
        $this->messagesProcessed = [];

        $container = $this->createMock(ContainerInterface::class);
        $worker = new Worker(
            ['test' => fn(MessageInterface $message): mixed => $this->messagesProcessed[] = $message->getData()],
            new NullLogger(),
            new Injector($container),
            $container,
            new ConsumeMiddlewareDispatcher($this->createMock(MiddlewareFactoryConsumeInterface::class)),
            new FailureMiddlewareDispatcher($this->createMock(MiddlewareFactoryFailureInterface::class), [])
        );

        $messages = [1, 'foo', 'bar-baz'];
        foreach ($messages as $message) {
            $worker->process(new Message('test', $message), $this->getQueue());
        }

        $this->assertEquals($messages, $this->messagesProcessed);
    }
}
