<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\FailureStrategy;

use Yiisoft\Yii\Queue\Message\MessageInterface;

final class ClearMetaStrategy implements FailureStrategyInterface
{
    /**
     * @var string[]
     */
    private array $metaKeys;

    public function __construct(string ...$metaKeys)
    {
        $this->metaKeys = $metaKeys;
    }

    public function handle(MessageInterface $message, $stack): void
    {
        $messageNew = $this->wrap($message);
        $stack->continue($messageNew);
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
