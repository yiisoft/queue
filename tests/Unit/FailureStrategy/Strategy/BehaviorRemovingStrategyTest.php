<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Unit\FailureStrategy\Strategy;

use PHPUnit\Framework\Assert;
use Yiisoft\Yii\Queue\FailureStrategy\Dispatcher\PipelineInterface;
use Yiisoft\Yii\Queue\FailureStrategy\Strategy\BehaviorRemovingStrategy;
use Yiisoft\Yii\Queue\FailureStrategy\Strategy\FailureStrategyInterface;
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
                ['testKey' => 'testValue'],
                [],
            ],
            [
                new BehaviorRemovingStrategy(),
                ['testKey' => 'testValue'],
                ['testKey' => 'testValue'],
            ],
            [
                new BehaviorRemovingStrategy('non-existing'),
                ['testKey' => 'testValue'],
                ['testKey' => 'testValue'],
            ],
            [
                new BehaviorRemovingStrategy('non-existing'),
                [],
                [],
            ],
            [
                new BehaviorRemovingStrategy('testKey', 'non-existing'),
                ['testKey' => 'testValue', 'testKey2' => 'testValue2'],
                ['testKey2' => 'testValue2'],
            ],
            [
                new BehaviorRemovingStrategy('testKey', 'non-existing'),
                ['testKey' => 'testValue', 'testKey2' => 'testValue2', 'testKey3' => 'testValue3'],
                ['testKey2' => 'testValue2', 'testKey3' => 'testValue3'],
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
    public function testBehaviorRemovingStrategy(
        FailureStrategyInterface $strategy,
        array $metaInitial,
        array $metaResult
    ): void {
        $id = 'testId';
        $name = 'test';
        $data = 'data';

        $resultAssertion = static function (MessageInterface $message) use ($id, $name, $data, $metaResult) {
            Assert::assertEquals($id, $message->getId());
            Assert::assertEquals($name, $message->getPayloadName());
            Assert::assertEquals($data, $message->getPayloadData());
            Assert::assertEquals($metaResult, $message->getPayloadMeta());

            $message->setId('testIdTwo');
            Assert::assertEquals('testIdTwo', $message->getId());

            return true;
        };
        $pipeline = $this->createMock(PipelineInterface::class);
        $pipeline->expects(self::once())
            ->method('handle')
            ->willReturnCallback($resultAssertion);

        $message = new Message($name, $data, $metaInitial);
        $message->setId($id);
        $result = $strategy->handle($message, $pipeline);
        self::assertTrue($result);
    }

    /**
     * BehaviorRemovingStrategy must return false when there is no another pipeline to pass the message
     */
    public function testBehaviorRemovingStrategyReturnFalse(): void
    {
        $message = new Message('test', null, ['testBehavior' => 'testValue']);
        $strategy = new BehaviorRemovingStrategy('testBehavior');
        $result = $strategy->handle($message, null);

        self::assertFalse($result);
    }

    /**
     * @dataProvider strategyProvider
     *
     * @param FailureStrategyInterface $strategy
     * @param array $metaInitial
     * @param array $metaResult
     */
    public function testBehaviorRemovingStrategyReturnFalseFromPipeline(
        FailureStrategyInterface $strategy,
        array $metaInitial,
        array $metaResult
    ): void {
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
