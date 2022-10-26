<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Message;

final class Message implements MessageInterface
{
    private ?string $id = null;

    public function __construct(
        private string $handlerName,
        private mixed $data,
    ) {
        $this->handlerName = $handlerName;
        $this->data = $data;
    }

    public function getHandlerName(): string
    {
        return $this->handlerName;
    }

    /**
     * @return mixed
     */
    public function getData(): mixed
    {
        return $this->data;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getId(): ?string
    {
        return $this->id;
    }
}
