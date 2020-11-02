<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Exception;

use Throwable;
use UnexpectedValueException;
use Yiisoft\FriendlyException\FriendlyExceptionInterface;
use Yiisoft\Yii\Queue\Driver\DriverInterface;
use Yiisoft\Yii\Queue\Message\Behaviors\BehaviorInterface;

class BehaviorNotSupportedException extends UnexpectedValueException implements FriendlyExceptionInterface
{
    private string $driver;
    private BehaviorInterface $behavior;

    public function __construct(
        string $driver,
        BehaviorInterface $behavior,
        string $message = '',
        int $code = 0,
        Throwable $previous = null
    ) {
        if ($message === '') {
            $behaviorName = get_class($behavior);
            $message = "$driver does not support message \"$behaviorName\".";
        }

        parent::__construct($message, $code, $previous);

        $this->driver = $driver;
        $this->behavior = $behavior;
    }

    public function getName(): string
    {
        return 'Behavior is not supported by current queue driver.';
    }

    public function getSolution(): ?string
    {
        $behaviorName = get_class($this->behavior);
        $driverInterfaceClass = DriverInterface::class;

        return <<<SOLUTION
            The given driver $this->driver does not support behavior $behaviorName, attached to the provided message.
            You should either avoid attaching this behavior to messages while using the $this->driver driver,
                or use another driver, which supports this behavior.
            Officially supported drivers are:
                - yiisoft/yii-queue-amqp

            You can implement your own driver with $driverInterfaceClass.
            SOLUTION;
    }
}
