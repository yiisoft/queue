<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Message;

final class Message extends AbstractMessage
{
    private string $name;
    /** @var mixed $data Message data, encodable by a used driver */
    private $data;

    /**
     * Message constructor.
     *
     * @param string $name
     * @param mixed $data Message data, encodable by a used driver
     */
    public function __construct(string $name, $data)
    {
        $this->name = $name;
        $this->data = $data;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }
}
