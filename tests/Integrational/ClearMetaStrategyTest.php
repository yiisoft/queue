<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Integrational;

use PHPUnit\Framework\Assert;
use Yiisoft\Yii\Queue\FailureStrategy\ClearMetaStrategy;
use Yiisoft\Yii\Queue\FailureStrategy\PipelineInterface;
use Yiisoft\Yii\Queue\Message\Message;
use Yiisoft\Yii\Queue\Message\MessageInterface;
use Yiisoft\Yii\Queue\Tests\TestCase;

class ClearMetaStrategyTest extends TestCase
{
    public function testTest(): void
    {
        $resultAssertion = static function (MessageInterface $message) {
            Assert::assertEquals([], $message->getPayloadMeta());

            return true;
        };
        $mock = $this->createMock(PipelineInterface::class);
        $mock->expects(self::once())
            ->method('handle')
            ->willReturnCallback($resultAssertion);

        $testMetaKey = 'testMeta';
        $message = new Message('test', null, [$testMetaKey => 'testMetaValue']);
        (new ClearMetaStrategy($testMetaKey))->handle($message, $mock);
    }
}
