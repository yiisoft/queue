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
    public function strategyProvider(): array
    {
        return [
            [
                new ClearMetaStrategy('testMeta'),
                ['testMeta' =>'value'],
                [],
                true,
            ],
        ];
    }
    /**
     * @dataProvider strategyProvider
     */
    public function testTest(\Yiisoft\Yii\Queue\FailureStrategy\FailureStrategyInterface $strategy, array $metaInitial, array $metaResult, bool $executionResult): void
    {
        $resultAssertion = static function (MessageInterface $message) use ($metaResult) {
            Assert::assertEquals($metaResult, $message->getPayloadMeta());

            return true;
        };
        $pipeline = $this->createMock(PipelineInterface::class);
        $pipeline->expects(self::once())
            ->method('handle')
            ->willReturnCallback($resultAssertion);

        $message = new Message('test', null, $metaInitial);
        $result = $strategy->handle($message, $pipeline);
        self::assertEquals($executionResult, $result);
    }
}
