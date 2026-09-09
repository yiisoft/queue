<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Provider;

use BackedEnum;
use Psr\Container\ContainerInterface;
use Throwable;
use Yiisoft\Definitions\Exception\InvalidConfigException;
use Yiisoft\Factory\StrictFactory;
use Yiisoft\Queue\AsyncQueueProducer;
use Yiisoft\Queue\QueueConsumer;
use Yiisoft\Queue\SyncQueueProducer;
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
    /** @var array<string, array<string, AsyncQueueProducer|SyncQueueProducer|QueueConsumer|Throwable>> */
    private array $resolved = [];
    /** @var list<string> */
    private array $producerQueueNames = [];
    /** @var list<string> */
    private array $consumerQueueNames = [];

    /** @param array<string, mixed> $definitions */
    public function __construct(
        array $definitions,
        private readonly ?ContainerInterface $container = null,
        private readonly bool $validate = true,
    ) {
        /** @var array<string, array<string, mixed>> $validatedDefinitions */
        $validatedDefinitions = $this->validateRoleMaps($definitions);
        $this->definitions = $validatedDefinitions;
        foreach ($this->definitions as $queueName => $roles) {
            if (array_key_exists('producer', $roles)) {
                $this->producerQueueNames[] = $queueName;
            }
            if (array_key_exists('consumer', $roles)) {
                $this->consumerQueueNames[] = $queueName;
            }
        }
    }

    public function getProducer(string|BackedEnum $queueName): AsyncQueueProducer|SyncQueueProducer
    {
        $producer = $this->get($queueName, 'producer');
        assert($producer instanceof AsyncQueueProducer || $producer instanceof SyncQueueProducer);
        return $producer;
    }

    public function hasProducer(string|BackedEnum $queueName): bool
    {
        return array_key_exists('producer', $this->definitions[StringNormalizer::normalize($queueName)] ?? []);
    }

    public function getProducerQueueNames(): array
    {
        return $this->producerQueueNames;
    }

    public function getConsumer(string|BackedEnum $queueName): QueueConsumer
    {
        $consumer = $this->get($queueName, 'consumer');
        assert($consumer instanceof QueueConsumer);
        return $consumer;
    }

    public function hasConsumer(string|BackedEnum $queueName): bool
    {
        return array_key_exists('consumer', $this->definitions[StringNormalizer::normalize($queueName)] ?? []);
    }

    public function getConsumerQueueNames(): array
    {
        return $this->consumerQueueNames;
    }

    private function get(string|BackedEnum $queueName, string $role): AsyncQueueProducer|SyncQueueProducer|QueueConsumer
    {
        $queueName = StringNormalizer::normalize($queueName);
        if (!array_key_exists($queueName, $this->definitions)) {
            throw new QueueNotFoundException($queueName);
        }
        if (!array_key_exists($role, $this->definitions[$queueName])) {
            throw new QueueNotFoundException(sprintf('Queue "%s" does not have the "%s" capability.', $queueName, $role));
        }
        if (isset($this->resolved[$queueName][$role])) {
            $result = $this->resolved[$queueName][$role];
            if ($result instanceof Throwable) {
                throw $result;
            }
            return $result;
        }
        try {
            $key = $queueName . ':' . $role;
            $factory = new StrictFactory([$key => $this->definitions[$queueName][$role]], $this->container, $this->validate);
            $result = $factory->create($key);
            if ($role === 'producer' ? (!$result instanceof AsyncQueueProducer && !$result instanceof SyncQueueProducer) : !$result instanceof QueueConsumer) {
                throw new InvalidQueueConfigException(sprintf(
                    'Queue "%s" role "%s" must implement "%s"; got "%s" (configuration path queues.%s.%s).',
                    $queueName,
                    $role,
                    $role === 'producer' ? AsyncQueueProducer::class . ' or ' . SyncQueueProducer::class : QueueConsumer::class,
                    get_debug_type($result),
                    $queueName,
                    $role,
                ));
            }
            assert($result instanceof AsyncQueueProducer || $result instanceof SyncQueueProducer || $result instanceof QueueConsumer);
            $this->resolved[$queueName][$role] = $result;
            return $result;
        } catch (InvalidQueueConfigException $exception) {
            $this->resolved[$queueName][$role] = $exception;
            throw $exception;
        } catch (InvalidConfigException $exception) {
            $wrapped = new InvalidQueueConfigException(sprintf(
                'Invalid queue "%s" role "%s" definition (configuration path queues.%s.%s): %s',
                $queueName,
                $role,
                $queueName,
                $role,
                $exception->getMessage(),
            ), previous: $exception);
            $this->resolved[$queueName][$role] = $wrapped;
            throw $wrapped;
        }
    }

    /** @param array<string, mixed> $definitions @return array<string, array<string, mixed>> */
    private function validateRoleMaps(array $definitions): array
    {
        /** @var array<string, array<string, mixed>> $result */
        $result = [];
        foreach ($definitions as $queueName => $roles) {
            if (!is_array($roles)) {
                throw new InvalidQueueConfigException(sprintf('Queue "%s" must be a role map containing "producer" and/or "consumer"; got "%s".', $queueName, get_debug_type($roles)));
            }
            $keys = array_keys($roles);
            $unknown = array_diff($keys, ['producer', 'consumer']);
            if ($unknown !== []) {
                throw new InvalidQueueConfigException(sprintf('Queue "%s" has unknown role key(s) "%s". Only "producer" and "consumer" are allowed.', $queueName, implode('", "', $unknown)));
            }
            if ($roles === []) {
                throw new InvalidQueueConfigException(sprintf('Queue "%s" role map must contain "producer" and/or "consumer".', $queueName));
            }
            /** @var array<string, mixed> $roles */
            $result[$queueName] = $roles;
        }
        return $result;
    }
}
