<?php
/**
 * @author      Wizacha DevTeam <dev@wizacha.com>
 * @license     Proprietary
 * @copyright   Copyright (c) Wizacha
 */
declare(strict_types=1);

namespace Wizacha\ElasticApm\Tests\Handler;

use PhilKra\Agent;
use PhilKra\Events\EventFactoryInterface;
use PhilKra\Events\Span;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Wizacha\ElasticApm\Service\AgentService;

class AgentServiceTest extends TestCase
{
    private $agentPhilkraService;
    private $logger;
    private $transactionName;
    private $spanName;

    protected function setUp(): void
    {
        parent::setUp();

        $this->agentPhilkraService = $this->getMockBuilder(Agent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->transactionName = 'Test Transaction';
        $this->spanName = 'Test Span';
    }

    /**
     * @covers  \Wizacha\ElasticApm\Service\AgentService::startTransaction
     */
    public function testStartNewTransaction(): void
    {
        $this->agentPhilkraService
            ->expects($this->once())
            ->method('startTransaction')
            ->with($this->transactionName)
        ;
        $this->logger->expects($this->never())
            ->method('warning')
        ;

        $agentService = new AgentService(
            true,
            $this->logger,
            $this->agentPhilkraService
        );

        static::assertInstanceOf(AgentService::class, $agentService->startTransaction($this->transactionName));
    }

    /**
     * @covers  \Wizacha\ElasticApm\Service\AgentService::startTransaction
     */
    public function testStartNewTransactionWhileAlreadyStarted(): void
    {
        $agentService = new AgentService(
            true,
            $this->logger,
            $this->agentPhilkraService
        );

        $this->agentPhilkraService->expects($this->once())
            ->method('startTransaction')
        ;
        $this->logger->expects($this->exactly(2))
            ->method('warning')
            ->with('Elastic APM wrapper transaction is already started')
        ;

        $agentService->startTransaction($this->transactionName);
        $agentService->startTransaction($this->transactionName);
        $agentService->startTransaction($this->transactionName);
    }

    /**
     * @covers  \Wizacha\ElasticApm\Service\AgentService::startTransaction
     */
    public function testStartNewTransactionWithFlagFalse(): void
    {
        $agentService = new AgentService(
            false,
            $this->logger,
            $this->agentPhilkraService
        );

        $this->agentPhilkraService->expects($this->never())
            ->method('startTransaction')
        ;

        static::assertInstanceOf(AgentService::class, $agentService->startTransaction($this->transactionName));
    }

    /**
     * @covers  \Wizacha\ElasticApm\Service\AgentService::stopTransaction
     */
    public function testStopExistentTransaction(): void
    {
        $agentService = new AgentService(
            true,
            $this->logger,
            $this->agentPhilkraService
        );

        $this->agentPhilkraService->expects($this->once())
            ->method('send')
        ;
        $this->agentPhilkraService->expects($this->once())
            ->method('stopTransaction')
        ;
        $this->logger->expects($this->never())
            ->method('warning')
        ;

        $transaction = $agentService->startTransaction($this->transactionName);
        $transaction = $transaction->stopTransaction()->getTransaction();

        static::assertNull($transaction);
    }

    /**
     * @covers  \Wizacha\ElasticApm\Service\AgentService::stopTransaction
     */
    public function testStopTransactionSendFailure(): void
    {
        $agentService = new AgentService(
            true,
            $this->logger,
            $this->agentPhilkraService
        );

        $this->logger->expects($this->once())
            ->method('error')
        ;
        $this->agentPhilkraService->method('send')
            ->will($this->throwException(new \Exception()))
        ;

        $transaction = $agentService->startTransaction($this->transactionName);
        $transaction->stopTransaction();
    }

    /**
     * @covers  \Wizacha\ElasticApm\Service\AgentService::stopTransaction
     */
    public function testStopNonExistentTransaction(): void
    {
        $agentService = new AgentService(
            true,
            $this->logger,
            $this->agentPhilkraService
        );

        $this->agentPhilkraService->expects($this->never())
            ->method('send')
        ;
        $this->agentPhilkraService->expects($this->never())
            ->method('stopTransaction')
        ;
        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Elastic APM wrapper: trying to stop a non-existing transaction.')
        ;

        $agentService->stopTransaction();
    }

    /**
     * @covers  \Wizacha\ElasticApm\Service\AgentService::startSpan
     */
    public function testStartSpanWithExistentTransaction(): void
    {
        $agentService = new AgentService(
            true,
            $this->logger,
            $this->agentPhilkraService
        );

        $this->logger->expects($this->never())
            ->method('warning')
        ;

        $agentService->startTransaction($this->transactionName);

        $span = $agentService->startSpan($this->spanName);

        static::assertInstanceOf(Span::class, $span);
    }

    /**
     * @covers  \Wizacha\ElasticApm\Service\AgentService::startSpan
     */
    public function testStartSpanWithNonExistentTransaction(): void
    {
        $agentService = new AgentService(
            true,
            $this->logger,
            $this->agentPhilkraService
        );

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Elastic APM wrapper: trying to start a span with a non-existing transaction.')
        ;

        $span = $agentService->startSpan($this->spanName);

        static::assertNull($span);
    }

    /**
     * @covers  \Wizacha\ElasticApm\Service\AgentService::stopSpan
     */
    public function testStopExistentSpan(): void
    {
        $spanMock = $this->getMockBuilder(Span::class)->disableOriginalConstructor()->getMock();
        $spanMock->expects($this->once())->method('stop');
        $spanMock->expects($this->once())->method('setStacktrace');

        $factoryMock = $this->getMockBuilder(EventFactoryInterface::class)->disableOriginalConstructor()->getMock();
        $factoryMock->expects($this->once())->method('newSpan')->will($this->returnValue($spanMock));

        $agentPhilkraService = $this->agentPhilkraService;
        $agentPhilkraService->method('factory')->will($this->returnValue($factoryMock));

        $agentService = new AgentService(
            true,
            $this->logger,
            $agentPhilkraService
        );

        $agentService->startTransaction($this->transactionName);

        $span = $agentService->startSpan($this->spanName);

        $this->logger->expects($this->never())
            ->method('warning')
        ;
        $this->agentPhilkraService->expects($this->once())
            ->method('putEvent')
            ->with($span);

        static::assertInstanceOf(AgentService::class, $agentService->stopSpan($span));
    }

    /**
     * @covers  \Wizacha\ElasticApm\Service\AgentService::stopSpan
     */
    public function testStopNonExistentSpan(): void
    {
        $agentService = new AgentService(
            true,
            $this->logger,
            $this->agentPhilkraService
        );

        $agentService->startTransaction($this->transactionName);

        $span = $agentService->startSpan($this->spanName);

        $agentService->stopSpan($span); // We stop it as we should

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Elastic APM wrapper: trying to stop a non-existing span.')
        ;

        // Then we try to pass the same object as contained in $span, but after having stopped it
        $agentService->stopSpan($span);
    }
}
