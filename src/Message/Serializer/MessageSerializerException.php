<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message\Serializer;

use RuntimeException;

/**
 * Thrown when message serialization/unserialization fails.
 */
final class MessageSerializerException extends RuntimeException {}
