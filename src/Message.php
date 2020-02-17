<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue;

use Yiisoft\Yii\Queue\Jobs\JobInterface;

class Message implements MessageInterface
{
    private string $id;
    private JobInterface $job;

    public function __construct(string $id, JobInterface $job)
    {
        $this->id = $id;
        $this->job = $job;
    }

    /**
     * @inheritDoc
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @inheritDoc
     */
    public function getJob(): JobInterface
    {
        return $this->job;
    }
}
