<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\ETL\Domain\Model;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;

interface ExecutorStep extends ExecutableStep
{
    /**
     * @param array<string, mixed> $arguments
     * @param array<string, mixed> $configuration
     */
    public function execute(string $command, array $arguments, Context $context, array $configuration = []): mixed;
}
