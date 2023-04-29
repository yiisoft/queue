<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Unit\Debug;

use PHPUnit\Framework\TestCase;
use Yiisoft\Yii\Queue\Adapter\AdapterInterface;
use Yiisoft\Yii\Queue\Debug\QueueCollector;
use Yiisoft\Yii\Queue\Debug\QueueDecorator;
use Yiisoft\Yii\Queue\QueueInterface;

class QueueDecoratorTest extends TestCase
{
    public function testWithAdapter()
    {
        $queue = $this->createMock(QueueInterface::class);
        $collector = new QueueCollector();
        $decorator = new QueueDecorator(
            $queue,
            $collector,
        );

        $queueAdapter = $this->createMock(AdapterInterface::class);

        $newDecorator = $decorator->withAdapter($queueAdapter);

        $this->assertInstanceOf(QueueDecorator::class, $newDecorator);
    }
}
