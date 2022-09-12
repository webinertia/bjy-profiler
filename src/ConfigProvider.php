<?php

namespace BjyProfiler;

use BjyProfiler\Db\Adapter\ProfilingAdapterFactory;
use Laminas\Db\Adapter\AdapterInterface;

class ConfigProvider
{
    /**
     * Retrieve BjyProfiler default configuration.
     *
     * @return array
     */
    public function __invoke()
    {
        return [
            'dependencies' => $this->getDependencyConfig(),
        ];
    }

    /**
     * Retrieve BjyProfiler default dependency configuration.
     *
     * @return array
     */
    public function getDependencyConfig()
    {
        return [
            'factories' => [
                AdapterInterface::class => ProfilingAdapterFactory::class,
            ],
        ];
    }
}
