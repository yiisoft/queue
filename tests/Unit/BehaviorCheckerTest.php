<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Unit;

use Psr\Log\NullLogger;
use Yiisoft\Yii\Queue\Adapter\BehaviorChecker;
use Yiisoft\Yii\Queue\Exception\BehaviorNotSupportedException;
use Yiisoft\Yii\Queue\Message\Behaviors\BehaviorInterface;
use Yiisoft\Yii\Queue\Tests\App\DummyBehavior;
use Yiisoft\Yii\Queue\Tests\App\DummyBehaviorChild;
use Yiisoft\Yii\Queue\Tests\TestCase;

final class BehaviorCheckerTest extends TestCase
{
    public function checkPassProvider(): array
    {
        return [
            'empty both' => [
                [],
                [],
            ],
            'empty current' => [
                [],
                [DummyBehavior::class],
            ],
            'direct coincide' => [
                [new DummyBehavior()],
                [DummyBehavior::class],
            ],
            'with inheritance' => [
                [new DummyBehaviorChild()],
                [DummyBehavior::class],
            ],
            'with interface inheritance' => [
                [new DummyBehavior()],
                [BehaviorInterface::class],
            ],
        ];
    }

    /**
     * @dataProvider checkPassProvider
     *
     * @param array $current
     * @param array $available
     */
    public function testCheckPass(array $current, array $available): void
    {
        (new BehaviorChecker())->check('TestAdapter', $current, $available);

        $this->addToAssertionCount(1);
    }

    public function checkFailProvider(): array
    {
        return [
            'empty available' => [
                [new DummyBehavior()],
                [],
            ],
            'different classes' => [
                [new DummyBehavior()],
                [new NullLogger()],
            ],
            'reverted inheritance' => [
                [new DummyBehavior()],
                [DummyBehaviorChild::class],
            ],
        ];
    }

    /**
     * @dataProvider checkFailProvider
     *
     * @param array $current
     * @param array $available
     */
    public function testCheckFail(array $current, array $available): void
    {
        $this->expectException(BehaviorNotSupportedException::class);
        (new BehaviorChecker())->check('TestAdapter', $current, $available);
    }
}
