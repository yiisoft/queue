<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message;

final class Message implements MessageInterface
{
    use MessageTrait;

    /**
     * @param mixed $data Message data, encodable by a queue adapter
     * @param array $metadata Message metadata, encodable by a queue adapter
     */
    public function __construct(
        mixed $data,
        array $metadata = [],
    ) {
        $this->data = $data;
        $this->metadata = $metadata;
    }
}
