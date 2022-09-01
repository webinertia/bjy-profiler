<?php

namespace Webinertia\BjyProfiler;

use Webinertia\BjyProfiler\Db\Adapter\ProfilingAdapterFactory;
use Laminas\Db\Adapter\AdapterInterface;

class ConfigProvider
{
    /**
     * Retrieve Webinertia\BjyProfiler default configuration.
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
     * Retrieve Webinertia\BjyProfiler default dependency configuration.
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
