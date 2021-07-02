<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Exception;

use InvalidArgumentException;
use Throwable;
use Yiisoft\FriendlyException\FriendlyExceptionInterface;
use Yiisoft\Yii\Queue\Adapter\AdapterInterface;
use Yiisoft\Yii\Queue\Message\Behaviors\BehaviorInterface;

class BehaviorMisconfiguredException extends InvalidArgumentException implements FriendlyExceptionInterface
{
    private string $adapterName;
    private BehaviorInterface $behavior;

    public function __construct(
        AdapterInterface $adapter,
        BehaviorInterface $behavior,
        string $message = '',
        int $code = 0,
        Throwable $previous = null
    ) {
        $adapterName = get_class($adapter);

        if ($message === '') {
            $behaviorName = get_class($behavior);
            $message = "Behavior \"$behaviorName\" for adapter \"$adapterName\" is incorrectly configured";
        }

        parent::__construct($message, $code, $previous);

        $this->adapterName = $adapterName;
        $this->behavior = $behavior;
    }

    public function getName(): string
    {
        return 'Queue behavior misconfigured';
    }

    public function getSolution(): ?string
    {
        // TODO: Implement getSolution() method.
    }
}
