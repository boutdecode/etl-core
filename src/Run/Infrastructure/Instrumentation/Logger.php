<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Run\Infrastructure\Instrumentation;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\Run\Domain\Instrumentation\Logger as RunLogger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

readonly class Logger implements RunLogger
{
    public function __construct(
        private LoggerInterface $logger,
        private NormalizerInterface $normalizable
    ) {
    }

    /**
     * @param array<string, mixed> $extractContext
     * @throws ExceptionInterface
     */
    public function info(string $message, Context $context, array $extractContext = []): void
    {
        $this->logger->info($message, array_merge(
            [
                'context' => $this->normalizable->normalize($context),
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
        $this->logger->debug($message, array_merge(
            [
                'context' => $this->normalizable->normalize($context),
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
        $this->logger->error($message, array_merge(
            [
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                //'trace' => $exception->getTraceAsString(),
                'context' => $this->normalizable->normalize($context),
            ],
            $extractContext
        ));
    }
}
