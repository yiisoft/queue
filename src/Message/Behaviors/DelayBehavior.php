<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Message\Behaviors;

final class DelayBehavior implements BehaviorInterface, DelayBehaviorInterface
{
    private int $delay;

    public function __construct(int $delay)
    {
        $this->delay = $delay;
    }

    /**
     * @inheritDoc
     */
    public function getConstructorParameters(): array
    {
        return [$this->delay];
    }

    public function getDelay(): int
    {
        return $this->delay;
    }
}
