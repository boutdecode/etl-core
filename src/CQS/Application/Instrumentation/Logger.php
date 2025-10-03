<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\CQS\Application\Instrumentation;

interface Logger
{
    public function start(string $chanel, mixed $context, bool $normalize = false): void;

    public function success(string $chanel, mixed $context, bool $normalize = false): void;

    /**
     * @param array<int|string, mixed> $context
     */
    public function error(string $chanel, string $reason, array $context = []): void;
}
