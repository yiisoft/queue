<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Provider;

use BackedEnum;
use Yiisoft\Queue\QueueInterface;

use function array_merge;
use function array_unique;
use function array_values;

/**
 * Composite queue provider.
 */
final class CompositeQueueProvider implements QueueProviderInterface
{
    /**
     * @var QueueProviderInterface[]
     */
    private readonly array $providers;

    /**
     * @param QueueProviderInterface ...$providers Queue providers to use.
     */
    public function __construct(
        QueueProviderInterface ...$providers,
    ) {
        $this->providers = $providers;
    }

    public function get(string|BackedEnum $name): QueueInterface
    {
        foreach ($this->providers as $provider) {
            if ($provider->has($name)) {
                return $provider->get($name);
            }
        }
        throw new QueueNotFoundException($name);
    }

    public function has(string|BackedEnum $name): bool
    {
        foreach ($this->providers as $provider) {
            if ($provider->has($name)) {
                return true;
            }
        }
        return false;
    }

    public function getNames(): array
    {
        $names = [];
        foreach ($this->providers as $provider) {
            $names[] = $provider->getNames();
        }
        return array_values(array_unique(array_merge(...$names)));
    }
}
