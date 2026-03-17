<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\CQS\Infrastructure\Instrumentation;

use BoutDeCode\ETLCoreBundle\CQS\Application\Instrumentation\Logger as InstrumentationLogger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class Logger implements InstrumentationLogger
{
    public const LOG_CHANNEL = 'log';

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly NormalizerInterface $normalizable
    ) {
    }

    /**
     * @throws ExceptionInterface
     */
    public function start(string $chanel, mixed $context, bool $normalize = false): void
    {
        if ($context === null) {
            $context = [];
        }

        $this->logger->info(
            $chanel,
            $normalize ?
                (array) $this->normalizable->normalize($context, null, [
                    'groups' => self::LOG_CHANNEL,
                ]) :
                (array) $context
        );
    }

    /**
     * @throws ExceptionInterface
     */
    public function success(string $chanel, mixed $context, bool $normalize = false): void
    {
        if ($context === null) {
            $context = [];
        }

        $this->logger->info(
            sprintf('%s.success', $chanel),
            $normalize ?
                (array) $this->normalizable->normalize($context, null, [
                    'groups' => self::LOG_CHANNEL,
                ]) :
                (array) $context
        );
    }

    public function error(string $chanel, string $reason, array $context = []): void
    {
        $this->logger->error(
            sprintf('%s.error', $chanel),
            [
                ...$context,
                'reason' => $reason,
            ]
        );
    }
}
