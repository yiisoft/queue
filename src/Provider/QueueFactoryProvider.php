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
    private array $producerNames = [];
    /** @var list<string> */
    private array $consumerNames = [];

    /** @param array<string, mixed> $definitions */
    public function __construct(
        array $definitions,
        private readonly ?ContainerInterface $container = null,
        private readonly bool $validate = true,
    ) {
        /** @var array<string, array<string, mixed>> $validatedDefinitions */
        $validatedDefinitions = $this->validateRoleMaps($definitions);
        $this->definitions = $validatedDefinitions;
        foreach ($this->definitions as $name => $roles) {
            if (array_key_exists('producer', $roles)) {
                $this->producerNames[] = $name;
            }
            if (array_key_exists('consumer', $roles)) {
                $this->consumerNames[] = $name;
            }
        }
    }

    public function getProducer(string|BackedEnum $name): QueueProducerInterface
    {
        $producer = $this->get($name, 'producer', QueueProducerInterface::class);
        assert($producer instanceof QueueProducerInterface);
        return $producer;
    }

    public function hasProducer(string|BackedEnum $name): bool
    {
        return array_key_exists('producer', $this->definitions[StringNormalizer::normalize($name)] ?? []);
    }

    public function getProducerNames(): array
    {
        return $this->producerNames;
    }

    public function getConsumer(string|BackedEnum $name): QueueConsumerInterface
    {
        $consumer = $this->get($name, 'consumer', QueueConsumerInterface::class);
        assert($consumer instanceof QueueConsumerInterface);
        return $consumer;
    }

    public function hasConsumer(string|BackedEnum $name): bool
    {
        return array_key_exists('consumer', $this->definitions[StringNormalizer::normalize($name)] ?? []);
    }

    public function getConsumerNames(): array
    {
        return $this->consumerNames;
    }

    /** @template T of QueueProducerInterface|QueueConsumerInterface @param class-string<T> $expected @return T */
    private function get(string|BackedEnum $name, string $role, string $expected): QueueProducerInterface|QueueConsumerInterface
    {
        $name = StringNormalizer::normalize($name);
        if (!array_key_exists($name, $this->definitions)) {
            throw new QueueNotFoundException($name);
        }
        if (!array_key_exists($role, $this->definitions[$name])) {
            throw new QueueNotFoundException(sprintf('Queue "%s" does not have the "%s" capability.', $name, $role));
        }
        if (isset($this->resolved[$name][$role])) {
            $result = $this->resolved[$name][$role];
            if ($result instanceof Throwable) {
                throw $result;
            }
            return $result;
        }
        try {
            $key = $name . ':' . $role;
            $factory = new StrictFactory([$key => $this->definitions[$name][$role]], $this->container, $this->validate);
            $result = $factory->create($key);
            if (!$result instanceof $expected) {
                throw new InvalidQueueConfigException(sprintf(
                    'Queue "%s" role "%s" must implement "%s"; got "%s" (configuration path queues.%s.%s).',
                    $name,
                    $role,
                    $expected,
                    get_debug_type($result),
                    $name,
                    $role,
                ));
            }
            assert($result instanceof QueueProducerInterface || $result instanceof QueueConsumerInterface);
            $this->resolved[$name][$role] = $result;
            return $result;
        } catch (InvalidQueueConfigException $exception) {
            $this->resolved[$name][$role] = $exception;
            throw $exception;
        } catch (InvalidConfigException $exception) {
            $wrapped = new InvalidQueueConfigException(sprintf(
                'Invalid queue "%s" role "%s" definition (configuration path queues.%s.%s): %s',
                $name,
                $role,
                $name,
                $role,
                $exception->getMessage(),
            ), previous: $exception);
            $this->resolved[$name][$role] = $wrapped;
            throw $wrapped;
        }
    }

    /** @param array<string, mixed> $definitions @return array<string, array<string, mixed>> */
    private function validateRoleMaps(array $definitions): array
    {
        /** @var array<string, array<string, mixed>> $result */
        $result = [];
        foreach ($definitions as $name => $roles) {
            if (!is_array($roles)) {
                throw new InvalidQueueConfigException(sprintf('Queue "%s" must be a role map containing "producer" and/or "consumer"; got "%s".', $name, get_debug_type($roles)));
            }
            $keys = array_keys($roles);
            $unknown = array_diff($keys, ['producer', 'consumer']);
            if ($unknown !== []) {
                throw new InvalidQueueConfigException(sprintf('Queue "%s" has unknown role key(s) "%s". Only "producer" and "consumer" are allowed.', $name, implode('", "', $unknown)));
            }
            if ($roles === []) {
                throw new InvalidQueueConfigException(sprintf('Queue "%s" role map must contain "producer" and/or "consumer".', $name));
            }
            /** @var array<string, mixed> $roles */
            $result[$name] = $roles;
        }
        return $result;
    }
}
