<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Exceptions;

use InvalidArgumentException;
use Throwable;
use Yiisoft\FriendlyException\FriendlyExceptionInterface;

class InvalidStatusException extends InvalidArgumentException implements FriendlyExceptionInterface
{
    private int $status;

    public function __construct(int $status, string $message = '', $code = 0, Throwable $previous = null)
    {
        if ($message === '') {
            $message = "Invalid status provided: $status";
        }
        parent::__construct($message, $code, $previous);
        $this->status = $status;
    }

    public function getName(): string
    {
        return 'Invalid job status provided';
    }

    public function getSolution(): ?string
    {
        return <<<SOLUTION
            Default available status values are:
            [1] JobStatus::WAITING
            [2] JobStatus::RESERVED
            [3] JobStatus::DONE

            Please consider using one of them or extending \Yiisoft\Yii\Queue\Enum\JobStatus class with your ows values.
            SOLUTION;
    }

    /**
     * Get the wrong status value
     *
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }
}
