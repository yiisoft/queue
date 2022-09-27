<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Message\Behaviors;

final class PriorityBehavior implements BehaviorInterface, PriorityBehaviorInterface
{
    public function __construct(private int $priority)
    {
    }

    public function getConstructorParameters(): array
    {
        return [$this->priority];
    }

    public function getPriority(): int
    {
        return $this->priority;
    }
}
