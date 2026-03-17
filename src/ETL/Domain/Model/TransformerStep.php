<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\ETL\Domain\Model;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;

interface TransformerStep extends ExecutableStep
{
    /**
     * @param array<string, mixed> $configuration
     */
    public function transform(mixed $data, Context $context, array $configuration = []): mixed;
}
