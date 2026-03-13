<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Provider;

use BackedEnum;
use Psr\Container\ContainerInterface;
use Yiisoft\Definitions\Exception\InvalidConfigException;
use Yiisoft\Factory\StrictFactory;
use Yiisoft\Queue\QueueInterface;
use Yiisoft\Queue\StringNormalizer;

use function array_key_exists;
use function sprintf;

/**
 * This queue provider creates queue objects directly from definitions.
 *
 * @see https://github.com/yiisoft/definitions/
 * @see https://github.com/yiisoft/factory/
 */
final class QueueFactoryProvider implements QueueProviderInterface
{
    /**
     * @psalm-var array<string, QueueInterface|null>
     */
    private array $queues = [];

    private readonly StrictFactory $factory;

    /**
     * @param array $definitions Queue definitions indexed by queue names.
     * @param ContainerInterface|null $container Container to use for dependencies resolving.
     * @param bool $validate If definitions should be validated when set.
     *
     * @psalm-param array<string, mixed> $definitions
     *
     * @throws InvalidQueueConfigException
     */
    public function __construct(
        array $definitions,
        ?ContainerInterface $container = null,
        bool $validate = true,
    ) {
        try {
            $this->factory = new StrictFactory($definitions, $container, $validate);
        } catch (InvalidConfigException $exception) {
            throw new InvalidQueueConfigException($exception->getMessage(), previous: $exception);
        }
    }

    public function get(string|BackedEnum $name): QueueInterface
    {
        $name = StringNormalizer::normalize($name);

        $queue = $this->getOrTryToCreate($name);
        if ($queue === null) {
            throw new QueueNotFoundException($name);
        }

        return $queue;
    }

    public function has(string|BackedEnum $name): bool
    {
        $name = StringNormalizer::normalize($name);
        return $this->factory->has($name);
    }

    /**
     * @throws InvalidQueueConfigException
     */
    private function getOrTryToCreate(string $name): ?QueueInterface
    {
        if (array_key_exists($name, $this->queues)) {
            return $this->queues[$name];
        }

        if (!$this->factory->has($name)) {
            $this->queues[$name] = null;
            return null;
        }

        try {
            $queue = $this->factory->create($name);
        } catch (InvalidConfigException $exception) {
            throw new InvalidQueueConfigException($exception->getMessage(), previous: $exception);
        }

        if (!$queue instanceof QueueInterface) {
            throw new InvalidQueueConfigException(
                sprintf(
                    'Queue must implement "%s". For queue "%s" got "%s" instead.',
                    QueueInterface::class,
                    $name,
                    get_debug_type($queue),
                ),
            );
        }

        $this->queues[$name] = $queue;
        return $queue;
    }
}
