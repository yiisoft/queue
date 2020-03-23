<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Exceptions;

use Throwable;
use UnexpectedValueException;
use Yiisoft\FriendlyException\FriendlyExceptionInterface;
use Yiisoft\Yii\Queue\DriverInterface;
use Yiisoft\Yii\Queue\Jobs\JobInterface;

class JobNotSupportedException extends UnexpectedValueException implements FriendlyExceptionInterface
{
    public function __construct(DriverInterface $driver, JobInterface $job, int $code = 0, Throwable $previous = null)
    {
        $driverClass = get_class($driver);
        $jobClass = get_class($job);
        $message = "$driverClass doesn't support jobs of $jobClass.";
        parent::__construct($message, $code, $previous);
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
        return <<<SOLUTION
            Try to use another driver which supports the given job.
            Give your attention to the interfaces this job implements.
            There are a few additional job interfaces available for your comfort:
            - AttemptsRestrictedJob
            - DelayableJobInterface
            - PrioritisedJobInterface
            - RetryableJobInterface
            But not every driver supports all of them.
            SOLUTION;
    }
}
