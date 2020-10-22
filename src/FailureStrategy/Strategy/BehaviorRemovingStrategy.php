<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\FailureStrategy\Strategy;

use Yiisoft\Yii\Queue\FailureStrategy\Dispatcher\PipelineInterface;
use Yiisoft\Yii\Queue\Message\MessageInterface;
use Yiisoft\Yii\Queue\Payload\PayloadInterface;

final class BehaviorRemovingStrategy implements FailureStrategyInterface
{
    /**
     * @var string[]
     */
    private array $metaKeys;

    /**
     * @param string ...$behaviors Behaviors are the keys in the payload metadata that should be removed
     *                             (e.g. {@see PayloadInterface::META_KEY_DELAY}]
     */
    public function __construct(string ...$behaviors)
    {
        $this->metaKeys = $behaviors;
    }

    public function handle(MessageInterface $message, ?PipelineInterface $pipeline): bool
    {
        if ($pipeline === null) {
            return false;
        }

        if ($this->metaKeys === []) {
            $messageNew = $message;
        } else {
            $messageNew = $this->wrap($message);
        }

        return $pipeline->handle($messageNew);
    }

    private function wrap(MessageInterface $message)
    {
        return new class($message, ...$this->metaKeys) implements MessageInterface {
            private ?string $id = null;
            private MessageInterface $message;
            private array $metaKeys;

            public function __construct(MessageInterface $message, string ...$metaKeys)
            {
                $this->message = $message;
                $this->metaKeys = $metaKeys;
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
                $meta = $this->message->getPayloadMeta();
                foreach ($this->metaKeys as $key) {
                    if (isset($meta[$key])) {
                        unset($meta[$key]);
                    }
                }

                return $meta;
            }
        };
    }
}
