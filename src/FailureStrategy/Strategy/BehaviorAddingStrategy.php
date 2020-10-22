<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\FailureStrategy\Strategy;

use Yiisoft\Yii\Queue\FailureStrategy\Dispatcher\PipelineInterface;
use Yiisoft\Yii\Queue\Message\MessageInterface;
use Yiisoft\Yii\Queue\Payload\PayloadInterface;

final class BehaviorAddingStrategy implements FailureStrategyInterface
{
    /**
     * @var string[]
     */
    private array $meta;

    /**
     * @param string ...$behaviors Behaviors are the keys in the payload metadata that should replace hte original ones
     *                             (e.g. {@see PayloadInterface::META_KEY_DELAY}]
     */
    public function __construct(string ...$behaviors)
    {
        $this->meta = $behaviors;
    }

    public function handle(MessageInterface $message, ?PipelineInterface $pipeline): bool
    {
        if ($pipeline === null) {
            return false;
        }

        if ($this->meta === []) {
            $messageNew = $message;
        } else {
            $messageNew = $this->wrap($message);
        }

        return $pipeline->handle($messageNew);
    }

    private function wrap(MessageInterface $message)
    {
        return new class($message, $this->meta) implements MessageInterface {
            private ?string $id = null;
            private MessageInterface $message;
            private array $meta;

            public function __construct(MessageInterface $message, array $meta)
            {
                $this->message = $message;
                $this->meta = $meta;
            }

            public function setId(?string $id): void
            {
                $this->id = $id;
            }

            public function getId(): ?string
            {
                return $this->id ?? $this->message->getId();
            }

            public function getPayloadName(): string
            {
                return $this->message->getPayloadName();
            }

            public function getPayloadData()
            {
                return $this->message->getPayloadData();
            }

            public function getPayloadMeta(): array
            {
                return array_merge($this->message->getPayloadMeta(), $this->meta);
            }
        };
    }
}
