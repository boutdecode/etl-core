<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\ETL\Domain\Model;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;

interface LoaderStep extends ExecutableStep
{
    /**
     * @param array<string, mixed> $configuration
     */
    public function load(mixed $data, mixed $destination, Context $context, array $configuration = []): mixed;
}
