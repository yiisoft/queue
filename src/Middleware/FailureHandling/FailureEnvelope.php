<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\FailureHandling;

use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Queue\Message\Envelope;
use Yiisoft\Queue\Message\MessageInterface;

use function array_key_exists;
use function is_array;

/**
 * @extends Envelope<array{
 *      yii-failure: array<string, scalar|null|array<scalar|null|array>>,
 *      ...<string, scalar|null|array<scalar|null|array>>
 *  }>
 */
final class FailureEnvelope extends Envelope
{
    public const META_FAILURE = 'yii-failure';

    private array $failureMeta;

    public function __construct(MessageInterface $message, array $failureMeta = [])
    {
        $this->failureMeta = $failureMeta === []
            ? self::getFailureMetaFromMessage($message)
            : ArrayHelper::merge(
                self::getFailureMetaFromMessage($message),
                $failureMeta,
            );
        parent::__construct($message, [
            self::META_FAILURE => $this->failureMeta,
        ]);
    }

    public function getFailureMeta(): array
    {
        return $this->failureMeta;
    }

    public function getFailureMetaValue(string $key, mixed $default = null): mixed
    {
        return $this->failureMeta[$key] ?? $default;
    }

    public static function fromMessage(MessageInterface $message): static
    {
        return new self(
            $message,
            self::getFailureMetaFromMessage($message),
        );
    }

    private static function getFailureMetaFromMessage(MessageInterface $message): array
    {
        $meta = $message->getMeta();
        if (array_key_exists(self::META_FAILURE, $meta)) {
            $result = $meta[self::META_FAILURE];
            return is_array($result) ? $result : [];
        }
        return [];
    }
}
