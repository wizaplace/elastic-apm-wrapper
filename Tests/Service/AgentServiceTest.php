<?php
/**
 * @author      Wizacha DevTeam <dev@wizacha.com>
 * @license     Proprietary
 * @copyright   Copyright (c) Wizacha
 */
declare(strict_types=1);

namespace Wizacha\ElasticApm\Tests\Handler;

use PhilKra\Agent;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Wizacha\ElasticApm\Service\AgentService;

class AgentServiceTest extends TestCase
{
    /**
     * @covers  \Wizacha\ElasticApm\Service\AgentService::startTransaction
     */
    public function testStartNewTransaction() : void
    {
        $transactionName = 'Transaction De Test';
        $agentPhilkraService = $this->getMockBuilder(Agent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $agentPhilkraService->expects($this->once())->method('startTransaction')->with($transactionName);

        $agentService = new AgentService(
            true,
            $this->getMockBuilder(LoggerInterface::class)->disableOriginalConstructor()->getMock(),
            $agentPhilkraService
        );
        static::assertInstanceOf(AgentService::class, $agentService->startTransaction($transactionName));
    }

    /**
     * @covers  \Wizacha\ElasticApm\Service\AgentService::startTransaction
     */
    public function testStartNewTransactionWhileAlreadyStarted(): void
    {
        $transactionName = 'Transaction De Test';
        $agentPhilkraService = $this->getMockBuilder(Agent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $agentService = new AgentService(
            true,
            $logger,
            $agentPhilkraService
        );

        $agentPhilkraService->expects($this->once())
            ->method('startTransaction')
        ;
        $logger->expects($this->exactly(2))
            ->method('warning')
            ->with('Elastic APM wrapper transaction is already started')
        ;

        $agentService->startTransaction($transactionName);
        $agentService->startTransaction($transactionName);
        $agentService->startTransaction($transactionName);
    }

    /**
     * @covers  \Wizacha\ElasticApm\Service\AgentService::startTransaction
     */
    public function testStartNewTransactionWithFlagFalse(): void
    {
        $transactionName = 'Transaction De Test';
        $agentPhilkraService = $this->getMockBuilder(Agent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $agentService = new AgentService(
            false,
            $this->getMockBuilder(LoggerInterface::class)->disableOriginalConstructor()->getMock(),
            $agentPhilkraService
        );

        static::assertNull($agentService->startTransaction($transactionName));
    }

    /**
     * @covers  \Wizacha\ElasticApm\Service\AgentService::stopTransaction
     */
    public function testStopExistentTransaction(): void
    {
        $transactionName = 'Transaction De Test';
        $agentPhilkraService = $this->getMockBuilder(Agent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $agentService = new AgentService(
            true,
            $this->getMockBuilder(LoggerInterface::class)->disableOriginalConstructor()->getMock(),
            $agentPhilkraService
        );

        $agentPhilkraService->expects($this->once())
            ->method('send')
        ;
        $agentPhilkraService->expects($this->once())
            ->method('stopTransaction')
        ;

        $transaction = $agentService->startTransaction($transactionName);
        $transaction = $transaction->stopTransaction()->getTransaction();

        static::assertNull($transaction);
    }

    /**
     * @covers  \Wizacha\ElasticApm\Service\AgentService::stopTransaction
     */
    public function testStopNonExistentTransaction(): void
    {
        $agentPhilkraService = $this->getMockBuilder(Agent::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $agentService = new AgentService(
            true,
            $logger,
            $agentPhilkraService
        );

        $agentPhilkraService->expects($this->never())
            ->method('send')
        ;
        $agentPhilkraService->expects($this->never())
            ->method('stopTransaction')
        ;
        $logger->expects($this->once())
            ->method('warning')
            ->with('Elastic APM wrapper: trying to stop a non-existing transaction.')
        ;

        $agentService->stopTransaction();
    }
}
