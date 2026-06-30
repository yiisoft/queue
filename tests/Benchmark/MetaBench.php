<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Benchmark;

use Generator;
use PhpBench\Attributes\ParamProviders;
use Yiisoft\Queue\Message\IdEnvelope;
use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\FailureHandling\FailureEnvelope;

final class MetaBench
{
    /**
     * Create metadata as an array and read its value from an array.
     */
    public function benchArrayRead(): void
    {
        $message = (new GenericMessage('foo', 'bar'))->withMeta(['id' => 1]);
        $id = $message->getMeta()['id'];
    }

    /**
     * Create metadata as an object and read its value immediately.
     */
    public function benchEnvelopeRead(): void
    {
        $message = new IdEnvelope(new GenericMessage('foo', 'bar'), 1);
        $id = $message->getId();
    }

    /**
     * Create metadata as an array and read its value from an envelope object.
     */
    public function benchEnvelopeReadRestored(): void
    {
        $message = IdEnvelope::fromMessage((new GenericMessage('foo', 'bar'))->withMeta(['id' => 1]));
        $id = $message->getId();
    }

    public function provideEnvelopeStack(): Generator
    {
        $config = [1 => 'one', 5 => 'five', 15 => 'fifteen'];
        $message = new IdEnvelope(new GenericMessage('foo', 'bar'), 1);

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
    public function benchEnvelopeStackCreation(array $params): void
    {
        $message = new GenericMessage('foo', 'bar');
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
    public function benchMetaArrayCreation(array $params): void
    {
        $meta = [FailureEnvelope::META_FAILURE => []];
        for ($i = 0; $i < $params[0]; $i++) {
            $meta[FailureEnvelope::META_FAILURE]["fail$i"] = "fail$i";
        }
        $message = (new GenericMessage('foo', 'bar'))->withMeta($meta);
    }
}
