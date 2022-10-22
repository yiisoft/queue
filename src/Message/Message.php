<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Message;

final class Message extends AbstractMessage
{
    private string $handlerName;
    /** @var mixed $data Message data, encodable by a used driver */
    private $data;

    /**
     * Message constructor.
     *
     * @param string $handlerName
     * @param mixed $data Message data, encodable by a used driver
     */
    public function __construct(string $handlerName, $data)
    {
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
    public function getData()
    {
        return $this->data;
    }
}
