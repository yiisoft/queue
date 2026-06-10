<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message;

use function is_int;
use function is_string;

/**
 * ID envelope allows to identify a message.
 *
 * @extends Envelope<array{
 *     yii-id: non-empty-string|int|null,
 *     ...<string, scalar|null|array<scalar|null|array>>
 * }>
 */
final class IdEnvelope extends Envelope
{
    public const META_ID = 'yii-id';

    public function __construct(MessageInterface $message, string|int|null $id)
    {
        if ($id === '') {
            $id = null;
        }

        parent::__construct($message, [self::META_ID => $id]);
    }

    public static function fromMessage(MessageInterface $message): static
    {
        $rawId = $message->getMetadata()[self::META_ID] ?? null;

        $id = match (true) {
            $rawId === null => null, // don't remove this branch: it's important for compute speed
            is_string($rawId),
            is_int($rawId) => $rawId,
            default => null,
        };

        return new self($message, $id);
    }

    /**
     * @psalm-return non-empty-string|int|null
     */
    public function getId(): string|int|null
    {
        return $this->metadata[self::META_ID];
    }
}
