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
use PhilKra\Events\Transaction;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\Test\TestLogger;
use Wizacha\ElasticApm\Service\AgentService;

class AgentServiceTest extends TestCase
{
    private $logger;

    protected function setUp(): void
    {
        $this->logger = new Logger('Superbe Logger');
    }


    /**
     * @covers  \Wizacha\ElasticApm\Service\AgentService::startTransaction
     */
    public function test_start_new_transaction() : void
    {
        $transactionName = 'Transaction De Test';

        $agentService = $this->getMockBuilder(Agent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $agentService->expects($this->once())->method('startTransaction')->with($transactionName);

        [$applicationName, $applicationVersion, $applicationEnvironment, $apmServerUrl, $apmSecretToken, $apmEnabled] = $this->getParams(true);

        $agentInstance = new AgentService(
            $apmEnabled,
            $this->getMockBuilder(LoggerInterface::class)->disableOriginalConstructor()->getMock(),
            $agentService
        );
        $this->assertInstanceOf(AgentService::class, $agentInstance->startTransaction($transactionName));
    }

    /**
     * @covers  \Wizacha\ElasticApm\Service\AgentService::startTransaction
     */
    public function test_start_new_transaction_while_already_started() : void
    {

    }


    /**
     * @covers  \Wizacha\ElasticApm\Service\AgentService::startTransaction
     * @throws \PhilKra\Exception\Transaction\DuplicateTransactionNameException
     */
//    public function testStartTransactionCreateTransaction(): void
//    {
//        $philkraAgent = $this->getMockBuilder('PhilKra\Agent')
//            ->disableOriginalConstructor()
//            ->getMock();
////
//        var_dump($philkraAgent);

//        $philkraAgent = new Agent();
//
//        $transaction = $this->getMockBuilder('PhilKra\Events\Transaction')
//            ->disableOriginalConstructor()
//            ->getMock();

//        var_dump($transaction);

//        $philkraAgent->method('startTransaction')->willReturn($transaction);

//        $logger = new Logger('Superbe Logger');
//
//        [$applicationName, $applicationVersion, $applicationEnvironment, $apmServerUrl, $apmSecretToken, $apmEnabled] = $this->getParams(true);
//
//        $agentInstance = new AgentService($applicationName, $applicationVersion, $applicationEnvironment, $apmServerUrl, $apmSecretToken, $apmEnabled, $logger);
//        $transactionTest = $agentInstance->startTransaction('Transaction de test');
//
//        static::assertInstanceOf(AgentService::class, $transactionTest);
//
//        // If APM is not enabled, transaction is not started (returns null)
//        [$applicationName, $applicationVersion, $applicationEnvironment, $apmServerUrl, $apmSecretToken, $apmEnabled] = $this->getParams(false);
//        $agentInstance = new AgentService($applicationName, $applicationVersion, $applicationEnvironment, $apmServerUrl, $apmSecretToken, $apmEnabled, $logger);
//        $transactionTest = $agentInstance->startTransaction('Transaction de test');
//
//        static::assertNull($transactionTest);

//        $this->createMock(Transaction::class)->start();



//    }

    private function getParams(bool $apmEnabled): array
    {
        return [
            'wizaplace', // applicationName
            '1.0', // applicationVersion
            'DEV', // applicationEnvironment
            'http://172.17.0.1:8200', // apmServerUrl
            'blablea', // apmSecretToken
            $apmEnabled // apmEnabled
        ];
    }
}
