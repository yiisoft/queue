<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Exceptions;

use Throwable;
use UnexpectedValueException;
use Yiisoft\FriendlyException\FriendlyExceptionInterface;
use Yiisoft\Yii\Queue\DriverInterface;
use Yiisoft\Yii\Queue\Jobs\DelayableJobInterface;
use Yiisoft\Yii\Queue\Jobs\JobInterface;
use Yiisoft\Yii\Queue\Jobs\PrioritisedJobInterface;
use Yiisoft\Yii\Queue\Jobs\RetryableJobInterface;

class JobNotSupportedException extends UnexpectedValueException implements FriendlyExceptionInterface
{
    private DriverInterface $driver;
    private JobInterface $job;

    public function __construct(
        DriverInterface $driver,
        JobInterface $job,
        string $message = '',
        int $code = 0,
        Throwable $previous = null
    ) {
        if ($message === '') {
            $driverClass = get_class($driver);
            $jobClass = get_class($job);
            $message = "$driverClass doesn't support jobs of $jobClass.";
        }

        parent::__construct($message, $code, $previous);

        $this->driver = $driver;
        $this->job = $job;
    }


    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'Job is not supported by current queue driver';
    }

    /**
     * @inheritDoc
     */
    public function getSolution(): ?string
    {
        $defaultInterfaces = [
            DelayableJobInterface::class,
            PrioritisedJobInterface::class,
            RetryableJobInterface::class,
        ];
        $interfaces = array_intersect($defaultInterfaces, class_implements($this->job));
        $interfaces = implode(', ', $interfaces);

        $jobClass = get_class($this->job);
        $driverClass = get_class($this->driver);

        return <<<SOLUTION
            The given job $jobClass implements next system interfaces:
            $interfaces.

            Here is a list of all default interfaces which can be unsupported by different queue drivers:
            - DelayableJobInterface (allows to execute job with a delay)
            - PrioritisedJobInterface (is used to prioritize job execution)
            - RetryableJobInterface (allows to execute the job multiple times while it fails)

            The given driver $driverClass does not support one of them, or even more.
            The solution is in one of these:
            - Check which interfaces does $driverClass support and remove not supported interfaces from $jobClass.
            - Use another driver which supports all interfaces you need. Officially supported drivers are:
                - None yet :) Work is in progress.
            SOLUTION;
    }
}
