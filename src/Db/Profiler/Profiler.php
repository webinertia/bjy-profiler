<?php

declare(strict_types=1);

namespace BjyProfiler\Db\Profiler;

use BjyProfiler\Exception\RuntimeException;
use Laminas\Db\Adapter\Profiler\ProfilerInterface;
use Laminas\Db\Adapter\StatementContainerInterface;

use function debug_backtrace;
use function end;
use function key;
use function ltrim;
use function phpversion;
use function substr;
use function strtolower;
use function version_compare;

use const DEBUG_BACKTRACE_IGNORE_ARGS;

final class Profiler implements ProfilerInterface
{
    /**
     * Logical OR these together to get a proper query type filter
     */
    public const CONNECT     = 1;
    public const QUERY       = 2;
    public const INSERT      = 4;
    public const UPDATE      = 8;
    public const DELETE      = 16;
    public const SELECT      = 32;
    public const TRANSACTION = 64;

    /** @var Query[] $profiles */
    protected $profiles = [];
    /** @var bool $enabled */
    protected $enabled;
    /** @var int $filterTypes */
    protected $filterTypes;
    /**
     * @param bool $enabled
     * @return void
     */
    public function __construct($enabled = true)
    {
        $this->enabled = $enabled;
        $this->filterTypes = 127;
    }

    public function enable(): self
    {
        $this->enabled = true;
        return $this;
    }

    public function disable(): self
    {
        $this->enabled = false;
        return $this;
    }

    public function setFilterQueryType(?int $queryTypes = null): self
    {
        $this->filterTypes = $queryTypes;
        return $this;
    }

    public function getFilterQueryType(): int
    {
        return $this->filterTypes;
    }

    public function startQuery(string $sql, ?array $parameters = null, ?array $stack = null): int|bool
    {
        if (! $this->enabled) {
            return false;
        }

        if (null === $stack) {
            if (version_compare('5.3.6', phpversion(), '<=')) {
                $stack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            } else {
                $stack = [];
            }
        }

        // try to detect the query type
        switch (strtolower(substr(ltrim($sql), 0, 6))) {
            case 'select':
                $queryType = static::SELECT;
                break;
            case 'insert':
                $queryType = static::INSERT;
                break;
            case 'update':
                $queryType = static::UPDATE;
                break;
            case 'delete':
                $queryType = static::DELETE;
                break;
            default:
                $queryType = static::QUERY;
                break;
        }

        $profile = new Query($sql, $queryType, $parameters, $stack);
        $this->profiles[] = $profile;
        $profile->start();

        end($this->profiles);
        return key($this->profiles);
    }

    public function endQuery(): bool
    {
        if (! $this->enabled) {
            return false;
        }

        if (empty($this->profiles)) {
            throw new RuntimeException('Query was not started.');
        }

        end($this->profiles)->end();
        return true;
    }

    /**
     * @return Query[]
     */
    public function getQueryProfiles(?int $queryTypes = null)
    {
        if (empty($this->profiles)) {
            return [];
        }

        $profiles = [];

        foreach ($this->profiles as $id => $profile) {
            if (null === $queryTypes) {
                $queryTypes = $this->filterTypes;
            }

            if ($profile->getQueryType() & $queryTypes) {
                $profiles[$id] = $profile;
            }
        }

        return $profiles;
    }

    /**
     * @param string|StatementContainerInterface $target
     */
    public function profilerStart($target): self
    {
        if ($target instanceof StatementContainerInterface) {
            $sql = $target->getSql();
            $params = $target->getParameterContainer()->getNamedArray();
        } else {
            $sql = $target;
            $params = [];
        }
        $this->startQuery($sql, $params);
        return $this;
    }

    public function profilerFinish(): self
    {
        $this->endQuery();
        return $this;
    }
}
