<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Job;

use Yiisoft\Serializer\SerializerInterface;

class CallableJob implements JobInterface
{
    private string $serialized;
    private SerializerInterface $serializer;

    public function __construct(callable $callable, SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
        $this->serialized = $serializer->serialize($callable);
    }

    public function execute(): void
    {
        $callable = $this->serializer->unserialize($this->serialized);
        $callable();
    }
}
