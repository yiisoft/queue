<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Message;

final class Message implements ParametrizedMessageInterface
{
    use ParametrizedMessageTrait;

    /**
     * @param mixed $data Message data, encodable by a queue adapter
     * @param array $metadata Message metadata, encodable by a queue adapter
     * @param string|null $id Message id
     */
    public function __construct(
        private string $handlerName,
        private mixed $data,
        private array $metadata = [],
        ?string $id = null
    ) {
        $this->setId($id);
    }

    public function getHandlerName(): string
    {
        return $this->handlerName;
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
