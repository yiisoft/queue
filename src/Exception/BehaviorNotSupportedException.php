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
    private string $adapter;
    private BehaviorInterface $behavior;

    public function __construct(
        string $adapter,
        BehaviorInterface $behavior,
        string $message = '',
        int $code = 0,
        Throwable $previous = null
    ) {
        if ($message === '') {
            $behaviorName = get_class($behavior);
            $message = "$adapter does not support message \"$behaviorName\".";
        }

        parent::__construct($message, $code, $previous);

        $this->adapter = $adapter;
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
            The given adapter $this->adapter does not support behavior $behaviorName, attached to the provided message.
            You should either avoid attaching this behavior to messages while using the $this->adapter adapter,
                or use another adapter, which supports this behavior.
            Officially supported adapters are:
                - yiisoft/yii-queue-amqp

            You can implement your own adapter with $adapterInterfaceClass.
            SOLUTION;
    }
}
