<?php

/**
 * @author      Wizacha DevTeam <dev@wizacha.com>
 * @copyright   Copyright (c) Wizacha
 * @license     Proprietary
 */

declare(strict_types=1);

namespace Wizacha\ElasticApm\Service;

use PhilKra\Agent;
use PhilKra\Events\Error;
use PhilKra\Events\Span;
use PhilKra\Events\Transaction;
use Psr\Log\LoggerInterface;

class AgentService
{
    /** @var Agent */
    private $agent;

    /** @var LoggerInterface */
    private $logger;

    /** @var Transaction|null */
    private $transaction;

    /** @var Span [] */
    private $spans;

    public function __construct(
        LoggerInterface $logger,
        Agent $agent
    ) {
        $this->agent = $agent;
        $this->logger = $logger;
        $this->spans = [];
    }

    /**
     * @return \Wizacha\ElasticApm\Service\AgentService
     *
     * @throws \PhilKra\Exception\Transaction\DuplicateTransactionNameException
     */
    public function startTransaction(string $name, array $context = []): self
    {
        if (false === $this->transaction instanceof Transaction) {
            $this->transaction = $this->agent->startTransaction($name, $context);
        } else {
            $this->logger->warning('Elastic APM wrapper transaction is already started');
        }

        return $this;
    }

    /**
     * @param mixed[] $meta
     *
     * @return \Wizacha\ElasticApm\Service\AgentService
     *
     * @throws \PhilKra\Exception\Transaction\UnknownTransactionException
     */
    public function stopTransaction(array $meta = []): self
    {
        if (true === $this->transaction instanceof Transaction) {
            $this->stopAllSpans();
            $this->agent->stopTransaction(
                $this->transaction->getTransactionName(),
                $meta
            );
            $this->transaction = null;

            try {
                $this->agent->send();
            } catch (\Throwable $throwable) {
                $this->logger->error(
                    'Impossible to send the transaction data to the APM. Error was:' . $throwable->getMessage()
                );
            }
        } else {
            $this->logger->warning('Elastic APM wrapper: trying to stop a non-existing transaction.');
        }

        return $this;
    }

    public function getTransaction(): ?Transaction
    {
        return $this->transaction;
    }

    /**
     * @param mixed[] $context
     *
     * @return \Wizacha\ElasticApm\Service\AgentService
     */
    public function error(\Throwable $throwable, array $context = []): self
    {
        if (true === $this->transaction instanceof Transaction) {
            $this->agent->captureThrowable($throwable, $context, $this->transaction);
        } else {
            $this->logger->warning('Elastic APM wrapper: trying to log an error with a non-existing transaction.');
        }

        return $this;
    }

    public function startSpan(string $name, Transaction $parent = null): ?Span
    {
        if (false === $this->transaction instanceof Transaction) {
            $this->logger->warning('Elastic APM wrapper: trying to start a span with a non-existing transaction.');

            return null;
        }

        $newSpan = $this->agent->factory()->newSpan($name, $parent ?? $this->transaction);
        $newSpan->start();
        $this->spans[$newSpan->getId()] = $newSpan;

        return $newSpan;
    }

    public function stopSpan(?Span $span): self
    {
        if (
            true === $span instanceof Span
            && true === array_key_exists($span->getId(), $this->spans)
        ) {
            $exception = new \Exception('Closing span #' . $span->getId());
            $errorClass = new Error($exception, []);
            $stackTrace = $errorClass->jsonSerialize()['error']['exception']['stacktrace'];
            $span->setStacktrace($stackTrace);
            $span->stop();
            $this->agent->putEvent($span);
            unset($this->spans[$span->getId()]);
        } else {
            $this->logger->warning('Elastic APM wrapper: trying to stop a non-existing span.');
        }

        return $this;
    }

    public function stopAllSpans(): self
    {
        foreach ($this->spans as $span) {
            $this->stopSpan($span);
        }

        return $this;
    }
}
