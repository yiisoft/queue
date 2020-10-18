<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Message;

use Yiisoft\Yii\Queue\Message\Behaviors\BehaviorInterface;

trait BehaviorTrait
{
    /**
     * @var BehaviorInterface[]
     */
    private array $behaviors = [];

    public function attachBehavior(BehaviorInterface $behavior): self
    {
        $this->behaviors[] = $behavior;

        return $this;
    }

    public function getBehaviors(): array
    {
        return $this->behaviors;
    }

    public function getBehavior(string $behaviorClassName): ?BehaviorInterface
    {
        $behavior = $this->getExactBehavior($behaviorClassName);
        if ($behavior === null) {
            $behavior = $this->getBehaviorLegacy($behaviorClassName);
        }

        return $behavior;
    }

    private function getExactBehavior(string $behaviorClassName): ?BehaviorInterface
    {
        $behaviorClassName = ltrim($behaviorClassName, '\\');
        foreach ($this->behaviors as $behavior) {
            if (get_class($behavior) === $behaviorClassName) {
                return $behavior;
            }
        }

        return null;
    }

    private function getBehaviorLegacy(string $behaviorClassName): ?BehaviorInterface
    {
        foreach ($this->behaviors as $behavior) {
            if ($behavior instanceof $behaviorClassName) {
                return $behavior;
            }
        }

        return null;
    }
}
