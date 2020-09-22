<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Message;

class Message implements MessageInterface
{
    private ?string $id;
    private string $payloadName;
    private $payloadData;
    private array $payloadMeta;

    public function __construct(string $payloadName, $payloadData, array $payloadMeta, ?string $id = null)
    {
        $this->id = $id;
        $this->payloadName = $payloadName;
        $this->payloadData = $payloadData;
        $this->payloadMeta = $payloadMeta;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getPayloadName(): string
    {
        return $this->payloadName;
    }

    public function getPayloadData()
    {
        return $this->payloadData;
    }

    public function getPayloadMeta(): array
    {
        return $this->payloadMeta;
    }
}
