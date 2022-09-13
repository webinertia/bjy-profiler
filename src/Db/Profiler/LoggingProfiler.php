<?php

declare(strict_types=1);

namespace BjyProfiler\Db\Profiler;

use Laminas\Log\Logger;

use function array_flip;
use function array_intersect_key;
use function array_shift;
use function count;
use function end;

final class LoggingProfiler extends Profiler
{
    /** @var Logger $logger */
    protected $logger;
    /** @var int $priority */
    protected $priority = Logger::DEBUG;
    /**
     * How many query profiles could be stored in memory.
     * Useful for long-running scripts with tons of queries that can take all the memory.
     * -1 - store all profiles
     * 0 - do not store any profiles
     * N > 0 - store N profiles, discard when there are more than N
     *
     * @var int $maxProfiles
     */
    protected $maxProfiles = 100;
    /**
     * Query parameters to log on query start
     *
     * @var array<int, string> $parametersStart
     * @see Query
     */
    protected $parametersStart = ['sql', 'parameters'];
    /**
     * Query parameters to log on query finish
     *
     * @var array<int, string> $parametersFinish
     * @see Query
     */
    protected $parametersFinish = ['elapsed'];

    public function __construct(Logger $logger, bool $enabled = true, array $options = [])
    {
        parent::__construct($enabled);
        $this->setLogger($logger);

        if (isset($options['priority'])) {
            $this->setPriority($options['priority']);
        }
        if (isset($options['maxProfiles'])) {
            $this->setMaxProfiles($options['maxProfiles']);
        }
        if (isset($options['parametersStart'])) {
            $this->setParametersStart($options['parametersStart']);
        }
        if (isset($options['parametersFinish'])) {
            $this->setParametersFinish($options['parametersFinish']);
        }
    }

    public function startQuery(string $sql, ?array $parameters = null, ?array $stack = null): int|bool
    {
        $result = parent::startQuery($sql, $parameters, $stack);
        $this->logStart();
        return $result;
    }

    public function endQuery(): bool
    {
        $result = parent::endQuery();
        $this->logEnd();
        $this->trimToMaxQueries();
        return $result;
    }

    private function logStart(): void
    {
        /** @var Query $lastQuery */
        $lastQuery = end($this->profiles);
        $this->getLogger()->log(
            $this->getPriority(),
            'Query started',
            array_intersect_key($lastQuery->toArray(), array_flip($this->getParametersStart()))
        );
    }

    private function logEnd(): void
    {
        /** @var Query $lastQuery */
        $lastQuery = end($this->profiles);
        $this->getLogger()->log(
            $this->getPriority(),
            'Query finished',
            array_intersect_key($lastQuery->toArray(), array_flip($this->getParametersFinish()))
        );
    }

    private function trimToMaxQueries(): void
    {
        $maxProfiles = $this->getMaxProfiles();
        if ($maxProfiles > -1 && count($this->profiles) > $maxProfiles) {
            array_shift($this->profiles);
        }
    }

    public function setPriority(int $level): self
    {
        $this->priority = $level;
        return $this;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setLogger(Logger $logger): self
    {
        $this->logger = $logger;
        return $this;
    }

    public function getLogger(): Logger
    {
        return $this->logger;
    }

    public function setMaxProfiles(int $maxProfiles): self
    {
        $this->maxProfiles = $maxProfiles;
        return $this;
    }

    public function getMaxProfiles(): int
    {
        return $this->maxProfiles;
    }

    public function setParametersFinish(array $parametersFinish): self
    {
        $this->parametersFinish = $parametersFinish;
        return $this;
    }

    public function getParametersFinish(): array
    {
        return $this->parametersFinish;
    }

    public function setParametersStart(array $parametersStart): self
    {
        $this->parametersStart = $parametersStart;
        return $this;
    }

    public function getParametersStart(): array
    {
        return $this->parametersStart;
    }
}
