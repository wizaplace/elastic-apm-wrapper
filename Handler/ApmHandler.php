<?php
declare(strict_types=1);

namespace Wizacha\ElasticApm;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Wizacha\Apm\AgentService as AgentService;

class ApmHandler extends AbstractProcessingHandler
{
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

    /** @var mixed[] $record */
    protected function write(array $record): void
    {
        if ($record['context']['exception'] instanceof \Throwable) {
            $this->agentService->error($record['context']['exception'], $record['context']);
        }

        $this->agentService->stopAllSpans();
        $this->agentService->stopTransaction();
    }
}
