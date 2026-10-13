<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Run\Infrastructure\Instrumentation;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\Core\Domain\Enum\LogVerbosity;
use BoutDeCode\ETLCoreBundle\Run\Domain\Instrumentation\Logger as RunLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

readonly class Logger implements RunLogger
{
    public function __construct(
        private LoggerInterface $logger,
        private NormalizerInterface $normalizable,
        private LogVerbosity $verbosity = LogVerbosity::INFO
    ) {
    }

    /**
     * @param array<string, mixed> $extractContext
     * @throws ExceptionInterface
     */
    public function info(string $message, Context $context, array $extractContext = []): void
    {
        if (! $this->verbosity->allows(LogLevel::INFO)) {
            return;
        }

        $this->logger->info($message, array_merge(
            [
                'context' => $this->formatContext($context),
            ],
            $extractContext
        ));
    }

    /**
     * @param array<string, mixed> $extractContext
     * @throws ExceptionInterface
     */
    public function debug(string $message, Context $context, array $extractContext = []): void
    {
        if (! $this->verbosity->allows(LogLevel::DEBUG)) {
            return;
        }

        $this->logger->debug($message, array_merge(
            [
                'context' => $this->formatContext($context),
            ],
            $extractContext
        ));
    }

    /**
     * @param array<string, mixed> $extractContext
     * @throws ExceptionInterface
     */
    public function error(string $message, Context $context, \Throwable $exception, array $extractContext = []): void
    {
        if (! $this->verbosity->allows(LogLevel::ERROR)) {
            return;
        }

        $this->logger->error($message, array_merge(
            [
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'context' => $this->formatContext($context),
                ...($this->verbosity->isDebug() ? [
                    'trace' => $exception->getTraceAsString(),
                ] : []),
            ],
            $extractContext
        ));
    }

    /**
     * @return array<array-key, mixed>|string|int|float|bool|\ArrayObject<array-key, mixed>|null
     * @throws ExceptionInterface
     */
    private function formatContext(Context $context): array|string|int|float|bool|\ArrayObject|null
    {
        if ($this->verbosity->isDebug()) {
            return $this->normalizable->normalize($context);
        }

        return [
            'pipeline' => $context->getPipeline()?->getName(),
            'step' => $context->getCurrentStep()?->getName(),
            'errors' => $context->getErrors(),
        ];
    }
}
