<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Run\Domain\Instrumentation;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;

interface Logger
{
    /**
     * @param array<string, mixed> $extractContext
     */
    public function info(string $message, Context $context, array $extractContext = []): void;

    /**
     * @param array<string, mixed> $extractContext
     */
    public function debug(string $message, Context $context, array $extractContext = []): void;

    /**
     * @param array<string, mixed> $extractContext
     */
    public function error(string $message, Context $context, \Throwable $exception, array $extractContext = []): void;
}
