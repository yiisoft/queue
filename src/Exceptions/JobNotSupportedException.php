<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Exceptions;

use Throwable;
use UnexpectedValueException;
use Yiisoft\Yii\Queue\DriverInterface;
use Yiisoft\Yii\Queue\Jobs\JobInterface;

class JobNotSupportedException extends UnexpectedValueException
{
    public function __construct(DriverInterface $driver, JobInterface $job, $code = 0, Throwable $previous = null)
    {
        $driverClass = get_class($driver);
        $jobClass = get_class($job);
        $message = "$driverClass doesn't support jobs of $jobClass.";
        parent::__construct($message, $code, $previous);
    }
}
