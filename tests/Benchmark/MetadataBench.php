<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Benchmark;

use Generator;
use PhpBench\Attributes\ParamProviders;
use PhpBench\Model\Tag;
use Yiisoft\Queue\Message\IdEnvelope;
use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\FailureHandling\FailureEnvelope;

final class MetadataBench
{
    /**
     * Create metadata as an array and read its value from an array.
     */
    #[Tag('metadata_read')]
    public function benchArrayRead(): void
    {
        $message = new Message('foo', 'bar', ['id' => 1]);
        $id = $message->getMetadata()['id'];
    }

    /**
     * Create metadata as an object and read its value immediately.
     */
    #[Tag('metadata_read')]
    public function benchEnvelopeRead(): void
    {
        $message = new IdEnvelope(new Message('foo', 'bar'), 1);
        $id = $message->getId();
    }

    /**
     * Create metadata as an array and read its value from an envelope object.
     */
    #[Tag('metadata_read')]
    public function benchEnvelopeReadRestored(): void
    {
        $message = IdEnvelope::fromMessage(new Message('foo', 'bar', ['id' => 1]));
        $id = $message->getId();
    }

    public function provideEnvelopeStack(): Generator
    {
        $config = [1 => 'one', 5 => 'three', 15 => 'fifteen'];
        $message = new IdEnvelope(new Message('foo', 'bar'), 1);

        for ($i = 1; $i <= max(...array_keys($config)); $i++) {
            if (isset($config[$i])) {
                yield $config[$i] => ['message' => $message];
            }
            $message = new FailureEnvelope($message, ["fail$i" => "fail$i"]);
        }
    }

    /**
     * Read metadata value from an envelope object restored from an envelope stacks of different depth
     *
     * @psalm-param array{message: MessageInterface} $params
     */
    #[ParamProviders('provideEnvelopeStack')]
    #[Tag('metadata_read')]
    public function benchEnvelopeReadFromStack(array $params): void
    {
        $id = IdEnvelope::fromMessage($params['message'])->getId();
    }

    public function provideEnvelopeStackCounts(): Generator
    {
        yield 'one' => [1];
        yield 'three' => [3];
        yield 'fifteen' => [15];
    }

    /**
     * Create envelope stack with the given depth
     *
     * @psalm-param array{0: int} $params
     */
    #[ParamProviders('provideEnvelopeStackCounts')]
    #[Tag('metadata_create')]
    public function benchEnvelopeStackCreation(array $params): void
    {
        $message = new Message('foo', 'bar');
        for ($i = 0; $i < $params[0]; $i++) {
            $message = new FailureEnvelope($message, ["fail$i" => "fail$i"]);
        }
    }

    /**
     * Create a metadata array with the given elements count
     *
     * @psalm-param array{0: int} $params
     */
    #[ParamProviders('provideEnvelopeStackCounts')]
    #[Tag('metadata_create')]
    public function benchMetadataArrayCreation(array $params): void
    {
        $metadata = ['failure-meta' => []];
        for ($i = 0; $i < $params[0]; $i++) {
            $metadata['failure-meta']["fail$i"] = "fail$i";
        }
        $message = new Message('foo', 'bar', $metadata);
    }
}
