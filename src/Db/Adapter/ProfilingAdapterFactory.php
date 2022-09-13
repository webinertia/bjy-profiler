<?php

declare(strict_types=1);

namespace BjyProfiler\Db\Adapter;

use BjyProfiler\Db\Profiler;
use Laminas\Log;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

use const PHP_SAPI;

final class ProfilingAdapterFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): ProfilingAdapter
    {
        $config = $container->get('Configuration');
        $adapter = new $requestedName($config['db']);

        if ('cli' === PHP_SAPI) {
            $logger = new Log\Logger();
            // write queries profiling info to stdout in CLI mode
            $writer = new Log\Writer\Stream('php://output');
            $logger->addWriter($writer, Log\Logger::DEBUG);
            $adapter->setProfiler(new Profiler\LoggingProfiler($logger));
        } else {
            $adapter->setProfiler(new Profiler\Profiler());
        }
        return $adapter;
    }
}
