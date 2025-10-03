<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\ETL\Domain\Model;

interface TransformerStep extends ExecutableStep
{
    /**
     * @param array<string, mixed> $configuration
     */
    public function transform(mixed $data, array $configuration = []): mixed;
}
