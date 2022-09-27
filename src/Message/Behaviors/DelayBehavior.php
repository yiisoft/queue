<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Message\Behaviors;

final class DelayBehavior implements BehaviorInterface, DelayBehaviorInterface
{
    public function __construct(private int $delay)
    {
    }

    public function getConstructorParameters(): array
    {
        return [$this->delay];
    }

    public function getDelay(): int
    {
        return $this->delay;
    }
}
