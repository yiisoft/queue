<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message\Serializer;

use RuntimeException;

/**
 * Thrown when a {@see MessageEncoderInterface} implementation fails to encode or decode a message.
 */
final class MessageEncoderException extends RuntimeException {}
