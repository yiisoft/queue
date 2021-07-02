<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Exception;

use Throwable;
use UnexpectedValueException;
use Yiisoft\FriendlyException\FriendlyExceptionInterface;
use Yiisoft\Yii\Queue\Adapter\AdapterInterface;
use Yiisoft\Yii\Queue\Message\Behaviors\BehaviorInterface;

class BehaviorNotSupportedException extends UnexpectedValueException implements FriendlyExceptionInterface
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
            $message = "$adapterName does not support message \"$behaviorName\".";
        }

        parent::__construct($message, $code, $previous);

        $this->adapterName = $adapterName;
        $this->behavior = $behavior;
    }

    public function getName(): string
    {
        return 'Behavior is not supported by current queue adapter.';
    }

    public function getSolution(): ?string
    {
        $behaviorName = get_class($this->behavior);
        $adapterInterfaceClass = AdapterInterface::class;

        return <<<SOLUTION
            The given adapter $this->adapterName does not support behavior $behaviorName, attached to the provided message.
            You should either avoid attaching this behavior to messages while using the $this->adapterName adapter,
                or use another adapter, which supports this behavior.
            Officially supported adapters are:
                - yiisoft/yii-queue-amqp

            You can implement your own adapter with $adapterInterfaceClass.
            SOLUTION;
    }
}
