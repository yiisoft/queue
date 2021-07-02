<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Message\Behaviors;

use InvalidArgumentException;

final class DelayBehavior implements BehaviorInterface
{
    private int $delay;

    public function __construct(int $delay)
    {
        $this->delay = $delay;
    }

    public static function fromData($data): self
    {
        if (!is_array($data) || !isset($data['delay'])) {
            var_dump($data);
            throw new InvalidArgumentException('Behavior restoration data is invalid');
        }

        return new self((int) $data['delay']);
    }

    public function getSerializableData(): array
    {
        return ['delay' => $this->delay];
    }

    public function getDelay(): int
    {
        return $this->delay;
    }
}
