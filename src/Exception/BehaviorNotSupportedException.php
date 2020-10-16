<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Exception;

use Throwable;
use UnexpectedValueException;
use Yiisoft\FriendlyException\FriendlyExceptionInterface;
use Yiisoft\Yii\Queue\Driver\DriverInterface;
use Yiisoft\Yii\Queue\Message\Behaviors\BehaviorInterface;
use Yiisoft\Yii\Queue\Message\MessageInterface;
use Yiisoft\Yii\Queue\Payload\DelayablePayloadInterface;
use Yiisoft\Yii\Queue\Payload\PayloadInterface;
use Yiisoft\Yii\Queue\Payload\PrioritisedPayloadInterface;
use Yiisoft\Yii\Queue\Payload\AttemptsRestrictedPayloadInterface;

class BehaviorNotSupportedException extends UnexpectedValueException implements FriendlyExceptionInterface
{
    private DriverInterface $driver;
    private BehaviorInterface $behavior;

    public function __construct(
        DriverInterface $driver,
        BehaviorInterface $behavior,
        string $message = '',
        int $code = 0,
        Throwable $previous = null
    ) {
        if ($message === '') {
            $driverClass = get_class($driver);
            $behaviorName = get_class($behavior);
            $message = "$driverClass does not support message \"$behaviorName\".";
        }

        parent::__construct($message, $code, $previous);

        $this->driver = $driver;
        $this->behavior = $behavior;
    }

    public function getName(): string
    {
        return 'Behavior is not supported by current queue driver';
    }

    public function getSolution(): ?string
    {
        $driverClass = get_class($this->driver);
        $behaviorName = get_class($this->behavior);
        $driverInterfaceClass = DriverInterface::class;

        return <<<SOLUTION
            The given driver $driverClass does not support behavior $behaviorName, attached to the provided message.
            You should either avoid attaching this behavior to messages while using the $driverClass driver,
                or use another driver, which supports this behavior.
            Officially supported drivers are:
                - yiisoft/yii-queue-amqp

            You can also implement your own driver within $driverInterfaceClass.
            SOLUTION;
    }
}
