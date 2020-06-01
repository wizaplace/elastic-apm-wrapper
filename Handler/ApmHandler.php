<?php

/**
 * @author      Wizacha DevTeam <dev@wizacha.com>
 * @copyright   Copyright (c) Wizacha
 * @license     Proprietary
 */

declare(strict_types=1);

namespace Wizacha\ElasticApm\Handler;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Wizacha\ElasticApm\Service\AgentService;

/**
 * This is a Monolog handler for ElasticAPM.
 */
class ApmHandler extends AbstractProcessingHandler
{
    public const CONTEXT_KEY = 'context';
    public const EXCEPTION_KEY = 'exception';
    public const MISSING_THROWABLE_ERROR = 'Elastic-APM wrapper : bad call to the Monolog Handle';

    /** @var AgentService */
    protected $agentService;

    public function __construct(
        AgentService $agentService,
        int $level = Logger::ERROR,
        bool $bubble = true
    ) {
        parent::__construct($level, $bubble);
        $this->agentService = $agentService;
    }

    /**
     * @param mixed[] $record is the array given by Monolog to the handlers
     *
     * @see \Monolog\Logger::addRecord() for the content of the $record array
     */
    protected function write(array $record): void
    {
        // Try the default behavior for the Monolog implementation
        if ($this->checkExceptionKey($record)) {
            $this->agentService->error($record[static::CONTEXT_KEY][static::EXCEPTION_KEY], $record[static::CONTEXT_KEY]);
        } else {
            /**
             * Just a safety net if the record is not well-structured.
             * Should never happen unless Monolog wiring is not properly done.
             */
            $ex = new \LogicException(static::MISSING_THROWABLE_ERROR);
            $this->agentService->error($ex, $record);
        }
    }

    /** @param mixed[] $record */
    protected function checkExceptionKey(array $record): bool
    {
        if (
            true === array_key_exists(static::CONTEXT_KEY, $record)
            && is_array($record[static::CONTEXT_KEY])
            && true === array_key_exists(static::EXCEPTION_KEY, $record[static::CONTEXT_KEY])
            && $record[static::CONTEXT_KEY][static::EXCEPTION_KEY] instanceof \Throwable
        ) {
            return true;
        }

        return  false;
    }
}
