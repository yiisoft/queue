<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Message\Behaviors;

class PriorityBehavior implements BehaviorInterface
{
    private int $priority;

    public function __construct(int $priority)
    {
        $this->priority = $priority;
    }

    /**
     * @inheritDoc
     */
    public function getState(): array
    {
        return ['priority' => $this->priority];
    }
}
