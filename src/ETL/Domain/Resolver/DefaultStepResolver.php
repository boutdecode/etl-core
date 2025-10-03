<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\ETL\Domain\Resolver;

use BoutDeCode\ETLCoreBundle\ETL\Domain\Model\ExecutableStep;

class DefaultStepResolver implements StepResolver
{
    /**
     * @var ExecutableStep[]
     */
    private array $steps = [];

    /**
     * @param iterable<ExecutableStep> $steps
     */
    public function __construct(
        iterable $steps = [],
    ) {
        foreach ($steps as $step) {
            $this->addStep($step);
        }
    }

    public function addStep(ExecutableStep $step): void
    {
        $this->steps[] = $step;
    }

    public function resolve(string $code): ?ExecutableStep
    {
        foreach ($this->steps as $step) {
            if ($step->getCode() === $code) {
                return $step;
            }
        }

        return null;
    }

    public function list(): array
    {
        return $this->steps;
    }
}
