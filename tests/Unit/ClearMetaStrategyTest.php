<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Unit;

use PHPUnit\Framework\Assert;
use Yiisoft\Yii\Queue\FailureStrategy\ClearMetaStrategy;
use Yiisoft\Yii\Queue\FailureStrategy\FailureStrategyInterface;
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
                new ClearMetaStrategy('testKey'),
                ['testKey' =>'testValue'],
                [],
            ],
            [
                new ClearMetaStrategy(),
                ['testKey' =>'testValue'],
                ['testKey' =>'testValue'],
            ],
            [
                new ClearMetaStrategy('non-existing'),
                ['testKey' =>'testValue'],
                ['testKey' =>'testValue'],
            ],
            [
                new ClearMetaStrategy('non-existing'),
                [],
                [],
            ],
            [
                new ClearMetaStrategy('testKey', 'non-existing'),
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
    public function testClearMetaStrategy(FailureStrategyInterface $strategy, array $metaInitial, array $metaResult): void
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
    public function testClearMetaStrategyReturnFalse(FailureStrategyInterface $strategy, array $metaInitial, array $metaResult): void
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
