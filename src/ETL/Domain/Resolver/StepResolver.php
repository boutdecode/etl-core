<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\ETL\Domain\Resolver;

use BoutDeCode\ETLCoreBundle\ETL\Domain\Model\ExecutableStep;

interface StepResolver
{
    public function resolve(string $code): ?ExecutableStep;

    /**
     * @return array<ExecutableStep>
     */
    public function list(): array;
}
