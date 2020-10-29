<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Message\Behaviors;

final class PriorityBehavior implements BehaviorInterface, PriorityBehaviorInterface
{
    private int $priority;

    public function __construct(int $priority)
    {
        $this->priority = $priority;
    }

    /**
     * @inheritDoc
     */
    public function getConstructorParameters(): array
    {
        return [$this->priority];
    }

    public function getPriority(): int
    {
        return $this->priority;
    }
}
