<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\ETL\Domain\Model;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;

interface ExtractorStep extends ExecutableStep
{
    /**
     * @param array<string, mixed> $configuration
     */
    public function extract(mixed $source, Context $context, array $configuration = []): mixed;
}
