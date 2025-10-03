<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\ETL\Domain\Model;

interface ExtractorStep extends ExecutableStep
{
    /**
     * @param array<string, mixed> $configuration
     */
    public function extract(mixed $source, array $configuration = []): mixed;
}
