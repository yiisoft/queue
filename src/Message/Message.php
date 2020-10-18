<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Message;

final class Message extends AbstractMessage
{
    private string $name;
    private $data;

    public function __construct(string $name, $data)
    {
        $this->name = $name;
        $this->data = $data;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getData()
    {
        return $this->data;
    }
}
