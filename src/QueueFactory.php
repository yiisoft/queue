<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue;

use Closure;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use WeakReference;
use Yiisoft\Definitions\ArrayDefinition;
use Yiisoft\Definitions\Exception\InvalidConfigException;
use Yiisoft\Definitions\Helpers\DefinitionValidator;
use Yiisoft\Factory\Factory;
use Yiisoft\Injector\Injector;
use Yiisoft\Yii\Queue\Adapter\AdapterInterface;
use Yiisoft\Yii\Queue\Exception\AdapterConfiguration\ChannelIncorrectlyConfigured;
use Yiisoft\Yii\Queue\Exception\AdapterConfiguration\ChannelNotConfiguredException;
use Yiisoft\Yii\Queue\Middleware\CallableFactory;
use Yiisoft\Yii\Queue\Middleware\InvalidCallableConfigurationException;

final class QueueFactory implements QueueFactoryInterface
{
    private array $queueCollection = [];

    /**
     * QueueFactory constructor.
     *
     * @param array<string, mixed> $channelConfiguration Configuration array in [channel_name => definition] format.
     * "Definition" here is a {@see Factory} definition
     * @param QueueInterface $queue A default queue implementation. `$queue->withAdapter()` will be returned
     * with the `get` method
     * @param Factory $yiiFactory
     * @param bool $enableRuntimeChannelDefinition A flag whether to enable a such behavior when there is no
     * explicit channel adapter definition: `return $this->queue->withAdapter($this->adapter->withChannel($channel)`
     * When this flag is set to false, only explicit definitions from the $definition parameter are used.
     * @param AdapterInterface|null $defaultAdapter A default adapter implementation.
     * It must be set when $enableRuntimeChannelDefinition is true.
     */
    public function __construct(
        private array $channelConfiguration,
        private QueueInterface $queue,
        private ContainerInterface $container,
        private CallableFactory $callableFactory,
        private Injector $injector,
        private bool $enableRuntimeChannelDefinition = false,
        private ?AdapterInterface $defaultAdapter = null,
    ) {
        if ($enableRuntimeChannelDefinition === true && $defaultAdapter === null) {
            $message = 'Either $enableRuntimeChannelDefinition must be false, or $defaultAdapter should be provided.';

            throw new InvalidArgumentException($message);
        }
    }

    public function get(string $channel = self::DEFAULT_CHANNEL_NAME): QueueInterface
    {
        if ($channel === $this->queue->getChannelName()) {
            return $this->queue;
        }

        if (isset($this->queueCollection[$channel]) && $this->queueCollection[$channel]->get() !== null) {
            $queue = $this->queueCollection[$channel]->get();
        } else {
            $queue = $this->create($channel);
            $this->queueCollection[$channel] = WeakReference::create($queue);
        }

        return $queue;
    }

    /**
     * @param string $channel
     *
     * @return QueueInterface
     * @throws ChannelIncorrectlyConfigured
     *
     */
    private function create(string $channel): QueueInterface
    {
        if (isset($this->channelConfiguration[$channel])) {
            $definition = $this->channelConfiguration[$channel];
            $this->checkDefinitionType($channel, $definition);
            $adapter = $this->createFromDefinition($channel, $definition);

            return $this->queue->withAdapter($adapter);
        }

        if ($this->enableRuntimeChannelDefinition === false) {
            throw new ChannelNotConfiguredException($channel);
        }

        /** @psalm-suppress PossiblyNullReference */
        return $this->queue->withAdapter($this->defaultAdapter->withChannel($channel));
    }

    private function checkDefinitionType(string $channel, mixed $definition): void
    {
        if (
            !$definition instanceof AdapterInterface
            && !is_array($definition)
            && !is_callable($definition)
            && !is_string($definition)
        ) {
            throw new ChannelIncorrectlyConfigured($channel, $definition);
        }
    }

    public function createFromDefinition(
        string $channel,
        AdapterInterface|callable|array|string $definition
    ): AdapterInterface {
        if ($definition instanceof AdapterInterface) {
            return $definition;
        }

        if (is_string($definition)) {
            return $this->getFromContainer($channel, $definition);
        }

        return $this->tryGetFromCallable($channel, $definition)
            ?? $this->tryGetFromArrayDefinition($channel, $definition)
            ?? throw new ChannelIncorrectlyConfigured($channel, $definition);
    }

    private function getFromContainer(string $channel, string $definition): AdapterInterface
    {
        if (class_exists($definition)) {
            if (is_subclass_of($definition, AdapterInterface::class)) {
                /** @var AdapterInterface */
                return $this->container->get($definition);
            }
        } elseif ($this->container->has($definition)) {
            $middleware = $this->container->get($definition);
            if ($middleware instanceof AdapterInterface) {
                return $middleware;
            }
        }

        throw new ChannelIncorrectlyConfigured($channel, $definition);
    }

    private function tryGetFromCallable(
        string $channel,
        callable|AdapterInterface|array|string $definition
    ): ?AdapterInterface {
        $callable = null;

        if ($definition instanceof Closure) {
            $callable = $definition;
        }

        if (
            is_array($definition)
            && array_keys($definition) === [0, 1]
        ) {
            try {
                $callable = $this->callableFactory->create($definition);
            } catch (InvalidCallableConfigurationException $exception) {
                throw new ChannelIncorrectlyConfigured($channel, $definition, previous: $exception);
            }
        }

        if ($callable !== null) {
            $adapter = $this->injector->invoke($callable);

            if (!$adapter instanceof AdapterInterface) {
                throw new ChannelIncorrectlyConfigured($channel, $definition);
            }
        }

        return null;
    }

    private function tryGetFromArrayDefinition(
        string $channel,
        callable|AdapterInterface|array|string $definition
    ): ?AdapterInterface {
        if (!is_array($definition)) {
            return null;
        }

        try {
            DefinitionValidator::validateArrayDefinition($definition);

            $middleware = ArrayDefinition::fromConfig($definition)->resolve($this->container);
            if ($middleware instanceof AdapterInterface) {
                return $middleware;
            }

            throw new ChannelIncorrectlyConfigured($channel, $definition);
        } catch (InvalidConfigException) {
        }

        throw new ChannelIncorrectlyConfigured($channel, $definition);
    }
}
