<?php
declare(strict_types=1);

namespace Wizacha\ElasticApm;

use PhilKra\Agent;
use PhilKra\Events\Span;
use PhilKra\Events\Transaction;
use Psr\Log\LoggerInterface;

final class AgentService
{
    /** @var Agent */
    private $agent;

    /** @var LoggerInterface|null */
    private $logger;

    /** @var Transaction|null */
    private $transaction;

    /** @var Span [] */
    private $spans;

    /** @var bool */
    private $apmEnabled;

    public function __construct(
        string $applicationName,
        string $applicationVersion,
        string $applicationEnvironment,
        string $apmServerUrl,
        string $apmSecretToken,
        bool $apmEnabled,
        LoggerInterface $logger = null
    ) {
        $this->apmEnabled = $apmEnabled;
        if ($this->apmEnabled === true) {
            $this->agent = new Agent(
                [
                    'appName' => $applicationName,
                    'appVersion' => $applicationVersion,
                    'environment' => $applicationEnvironment,
                    'serverUrl' => $apmServerUrl,
                    'secretToken' => $apmSecretToken,
                ]
            );
        }
        $this->logger = $logger;
        $this->spans = [];
    }

    /** @var mixed[] $context */
    public function startTransaction(string $name, array $context = []): ?self
    {
        if ($this->apmEnabled === false) {
            return null;
        }

        $this->transaction = $this->agent->startTransaction($name, $context);

        return $this;
    }

    /** @var mixed[] $meta */
    public function stopTransaction(array $meta = []): self
    {
        if (false === $this->transaction instanceof Transaction) {
            return $this;
        }

        $this->agent->stopTransaction(
            $this->transaction->getTransactionName(),
            $meta
        );

        $this->transaction = null;

        try {
            $this->agent->send();
        } catch (\Throwable $throwable) {
            if ($this->logger instanceof LoggerInterface) {
                $this->logger->error($throwable->getMessage());
            }
        }

        return $this;
    }

    /** @var mixed[] $context */
    public function error(\Throwable $throwable, array $context = []): self
    {
        $transaction = $this->transaction;

        if ($transaction instanceof Transaction === false) {
            return $this;
        }

        $this->agent->captureThrowable($throwable, $context, $transaction);

        return $this;
    }

        public function startSpan(string $name, Transaction $parent = null): ?Span
    {
        $transaction = $this->transaction;

        if ($transaction instanceof Transaction === false) {
            return null;
        }

        $newSpan = $this->agent->factory()->newSpan($name, $parent ?? $transaction);
        $newSpan->start();

        $this->spans[] = $newSpan;

        return $newSpan;
    }

    public function stopSpan(Span $span): self
    {
        $span->stop();
        $this->agent->putEvent($span);

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
