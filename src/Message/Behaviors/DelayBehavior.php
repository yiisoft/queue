<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Message\Behaviors;

class DelayBehavior implements BehaviorInterface
{
    private int $delay;

    public function __construct(int $delay)
    {
        $this->delay = $delay;
    }

    /**
     * @inheritDoc
     */
    public function getState(): array
    {
        return ['delay' => $this->delay];
    }
}
