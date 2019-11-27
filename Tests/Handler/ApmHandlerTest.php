<?php
/**
 * @author      Wizacha DevTeam <dev@wizacha.com>
 * @license     Proprietary
 * @copyright   Copyright (c) Wizacha
 */
declare(strict_types=1);

namespace Wizacha\ElasticApm\Tests\Handler;

use Monolog\Logger;
use PhilKra\Agent;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Wizacha\ElasticApm\Handler\ApmHandler;
use Wizacha\ElasticApm\Service\AgentService;

class ApmHandlerTest extends TestCase
{
    /**
     * Test the use case when Monolog has not been properly wired AND you wan to use this handler
     *
     * @covers \Wizacha\ElasticApm\Handler\ApmHandler::write
     */
    public function testMissingThrowable(): void
    {
        $agentService = $this->getMockBuilder(AgentService::class)->disableOriginalConstructor()->getMock();
        $agentService->method('getApmEnabled')->will($this->returnValue(true));
        $agentService->expects($this->once())->method('error')->with(
            $this->callback(function (\Exception $exception) {
                return ApmHandler::MISSING_THROWABLE_ERROR === $exception->getMessage();
            }
        ));

        $apmHandler = new ApmHandler($agentService, Logger::ERROR);
        $apmHandler->handle(['level' => Logger::ERROR, 'extra' => [], 'context' => []]);
    }

    /**
     * @covers \Wizacha\ElasticApm\Handler\ApmHandler::write
     */
    public function testNormalUseCase(): void
    {
        $exception = new \LogicException('Oops, something is broken');
        $context = [
            ApmHandler::EXCEPTION_KEY => $exception,
            ApmHandler::CONTEXT_KEY => [],
        ];

        $agentService = $this->getMockBuilder(AgentService::class)->disableOriginalConstructor()->getMock();
        $agentService->method('getApmEnabled')->will($this->returnValue(true));
        $agentService->expects($this->once())->method('error')->with($exception, $context);

        $apmHandler = new ApmHandler($agentService, Logger::ERROR);
        $apmHandler->handle(['level' => Logger::ERROR, 'extra' => [], 'context' => $context]);
    }

    public function testNoLogWithApmNotEnabled()
    {
        $exception = new \LogicException('Oops, something is broken');
        $context = [
            ApmHandler::EXCEPTION_KEY => $exception,
            ApmHandler::CONTEXT_KEY => [],
        ];

        $agentService = $this->getMockBuilder(AgentService::class)->disableOriginalConstructor()->getMock();

        $agentService->method('getApmEnabled')->will($this->returnValue(false));

        $apmHandler = new ApmHandler($agentService, Logger::ERROR);

        $agentService->expects($this->never())->method('error');

        $apmHandler->handle(['level' => Logger::ERROR, 'extra' => [], 'context' => $context]);

    }
}
