<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Job;

class CallableJob implements JobInterface
{
    /**
     * @var callable
     */
    private $callable;

    public function __construct(callable $callable)
    {
        $this->callable = $callable;
    }

    public function execute(): void
    {
        $callable = $this->callable;
        $callable();
    }
}
