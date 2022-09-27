<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Message;

final class Message extends AbstractMessage
{
    /**
     * Message constructor.
     *
     * @param mixed $data Message data, encodable by a used driver
     */
    public function __construct(private string $handlerName, private mixed $data)
    {
    }

    public function getHandlerName(): string
    {
        return $this->handlerName;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }
}
