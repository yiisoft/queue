<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Message\Behaviors;

use InvalidArgumentException;

final class PriorityBehavior implements BehaviorInterface
{
    private int $priority;

    public function __construct(int $priority)
    {
        $this->priority = $priority;
    }

    public static function fromData($data): self
    {
        if (!is_array($data) || !isset($data['priority'])) {
            throw new InvalidArgumentException('Behavior restoration data is invalid');
        }

        return new self((int) $data['priority']);
    }

    public function getSerializableData(): array
    {
        return ['priority' => $this->priority];
    }

    public function getPriority(): int
    {
        return $this->priority;
    }
}
