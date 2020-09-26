<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Unit;

use PHPUnit\Framework\Assert;
use Yiisoft\Yii\Queue\FailureStrategy\BehaviorRemovingStrategy;
use Yiisoft\Yii\Queue\FailureStrategy\FailureStrategyInterface;
use Yiisoft\Yii\Queue\FailureStrategy\PipelineInterface;
use Yiisoft\Yii\Queue\Message\Message;
use Yiisoft\Yii\Queue\Message\MessageInterface;
use Yiisoft\Yii\Queue\Tests\TestCase;

class BehaviorRemovingStrategyTest extends TestCase
{
    public function strategyProvider(): array
    {
        return [
            [
                new BehaviorRemovingStrategy('testKey'),
                ['testKey' =>'testValue'],
                [],
            ],
            [
                new BehaviorRemovingStrategy(),
                ['testKey' =>'testValue'],
                ['testKey' =>'testValue'],
            ],
            [
                new BehaviorRemovingStrategy('non-existing'),
                ['testKey' =>'testValue'],
                ['testKey' =>'testValue'],
            ],
            [
                new BehaviorRemovingStrategy('non-existing'),
                [],
                [],
            ],
            [
                new BehaviorRemovingStrategy('testKey', 'non-existing'),
                ['testKey' =>'testValue', 'testKey2' =>'testValue2'],
                ['testKey2' =>'testValue2'],
            ],
        ];
    }

    /**
     * @dataProvider strategyProvider
     *
     * @param FailureStrategyInterface $strategy
     * @param array $metaInitial
     * @param array $metaResult
     */
    public function testBehaviorRemovingStrategy(FailureStrategyInterface $strategy, array $metaInitial, array $metaResult): void
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
        self::assertTrue($result);
    }

    /**
     * @dataProvider strategyProvider
     *
     * @param FailureStrategyInterface $strategy
     * @param array $metaInitial
     * @param array $metaResult
     */
    public function testBehaviorRemovingStrategyReturnFalse(FailureStrategyInterface $strategy, array $metaInitial, array $metaResult): void
    {
        $resultAssertion = static function (MessageInterface $message) use ($metaResult) {
            Assert::assertEquals($metaResult, $message->getPayloadMeta());

            return false;
        };
        $pipeline = $this->createMock(PipelineInterface::class);
        $pipeline->expects(self::once())
            ->method('handle')
            ->willReturnCallback($resultAssertion);

        $message = new Message('test', null, $metaInitial);
        $result = $strategy->handle($message, $pipeline);
        self::assertFalse($result);
    }
}
