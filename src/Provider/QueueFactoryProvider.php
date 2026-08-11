<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Provider;

use BackedEnum;
use Psr\Container\ContainerInterface;
use Throwable;
use Yiisoft\Definitions\Exception\InvalidConfigException;
use Yiisoft\Factory\StrictFactory;
use Yiisoft\Queue\QueueConsumerInterface;
use Yiisoft\Queue\QueueProducerInterface;
use Yiisoft\Queue\StringNormalizer;

use function array_key_exists;
use function array_keys;
use function get_debug_type;
use function implode;
use function is_array;
use function sprintf;
use function assert;

/** Lazily creates producer and consumer roles from strict nested role maps. */
final class QueueFactoryProvider implements QueueProducerProviderInterface, QueueConsumerProviderInterface
{
    /** @var array<string, array<string, mixed>> */
    private array $definitions;
    /** @var array<string, array<string, QueueProducerInterface|QueueConsumerInterface|Throwable>> */
    private array $resolved = [];
    /** @var list<string> */
    private array $producerQueues = [];
    /** @var list<string> */
    private array $consumerQueues = [];

    /** @param array<string, mixed> $definitions */
    public function __construct(
        array $definitions,
        private readonly ?ContainerInterface $container = null,
        private readonly bool $validate = true,
    ) {
        /** @var array<string, array<string, mixed>> $validatedDefinitions */
        $validatedDefinitions = $this->validateRoleMaps($definitions);
        $this->definitions = $validatedDefinitions;
        foreach ($this->definitions as $queue => $roles) {
            if (array_key_exists('producer', $roles)) {
                $this->producerQueues[] = $queue;
            }
            if (array_key_exists('consumer', $roles)) {
                $this->consumerQueues[] = $queue;
            }
        }
    }

    public function getProducer(string|BackedEnum $queue): QueueProducerInterface
    {
        $producer = $this->get($queue, 'producer', QueueProducerInterface::class);
        assert($producer instanceof QueueProducerInterface);
        return $producer;
    }

    public function hasProducer(string|BackedEnum $queue): bool
    {
        return array_key_exists('producer', $this->definitions[StringNormalizer::normalize($queue)] ?? []);
    }

    public function getProducerQueues(): array
    {
        return $this->producerQueues;
    }

    public function getConsumer(string|BackedEnum $queue): QueueConsumerInterface
    {
        $consumer = $this->get($queue, 'consumer', QueueConsumerInterface::class);
        assert($consumer instanceof QueueConsumerInterface);
        return $consumer;
    }

    public function hasConsumer(string|BackedEnum $queue): bool
    {
        return array_key_exists('consumer', $this->definitions[StringNormalizer::normalize($queue)] ?? []);
    }

    public function getConsumerQueues(): array
    {
        return $this->consumerQueues;
    }

    /** @template T of QueueProducerInterface|QueueConsumerInterface @param class-string<T> $expected @return T */
    private function get(string|BackedEnum $queue, string $role, string $expected): QueueProducerInterface|QueueConsumerInterface
    {
        $queue = StringNormalizer::normalize($queue);
        if (!array_key_exists($queue, $this->definitions)) {
            throw new QueueNotFoundException($queue);
        }
        if (!array_key_exists($role, $this->definitions[$queue])) {
            throw new QueueNotFoundException(sprintf('Queue "%s" does not have the "%s" capability.', $queue, $role));
        }
        if (isset($this->resolved[$queue][$role])) {
            $result = $this->resolved[$queue][$role];
            if ($result instanceof Throwable) {
                throw $result;
            }
            return $result;
        }
        try {
            $key = $queue . ':' . $role;
            $factory = new StrictFactory([$key => $this->definitions[$queue][$role]], $this->container, $this->validate);
            $result = $factory->create($key);
            if (!$result instanceof $expected) {
                throw new InvalidQueueConfigException(sprintf(
                    'Queue "%s" role "%s" must implement "%s"; got "%s" (configuration path queues.%s.%s).',
                    $queue,
                    $role,
                    $expected,
                    get_debug_type($result),
                    $queue,
                    $role,
                ));
            }
            assert($result instanceof QueueProducerInterface || $result instanceof QueueConsumerInterface);
            $this->resolved[$queue][$role] = $result;
            return $result;
        } catch (InvalidQueueConfigException $exception) {
            $this->resolved[$queue][$role] = $exception;
            throw $exception;
        } catch (InvalidConfigException $exception) {
            $wrapped = new InvalidQueueConfigException(sprintf(
                'Invalid queue "%s" role "%s" definition (configuration path queues.%s.%s): %s',
                $queue,
                $role,
                $queue,
                $role,
                $exception->getMessage(),
            ), previous: $exception);
            $this->resolved[$queue][$role] = $wrapped;
            throw $wrapped;
        }
    }

    /** @param array<string, mixed> $definitions @return array<string, array<string, mixed>> */
    private function validateRoleMaps(array $definitions): array
    {
        /** @var array<string, array<string, mixed>> $result */
        $result = [];
        foreach ($definitions as $queue => $roles) {
            if (!is_array($roles)) {
                throw new InvalidQueueConfigException(sprintf('Queue "%s" must be a role map containing "producer" and/or "consumer"; got "%s".', $queue, get_debug_type($roles)));
            }
            $keys = array_keys($roles);
            $unknown = array_diff($keys, ['producer', 'consumer']);
            if ($unknown !== []) {
                throw new InvalidQueueConfigException(sprintf('Queue "%s" has unknown role key(s) "%s". Only "producer" and "consumer" are allowed.', $queue, implode('", "', $unknown)));
            }
            if ($roles === []) {
                throw new InvalidQueueConfigException(sprintf('Queue "%s" role map must contain "producer" and/or "consumer".', $queue));
            }
            /** @var array<string, mixed> $roles */
            $result[$queue] = $roles;
        }
        return $result;
    }
}
