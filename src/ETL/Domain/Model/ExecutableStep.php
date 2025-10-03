<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\ETL\Domain\Model;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Step;

interface ExecutableStep extends Step
{
    /**
     * @return array<string, mixed>
     */
    public function getConfigurationDescription(): array;

    public function setName(string $name): void;

    /**
     * @param array<string, mixed> $configuration
     */
    public function setConfiguration(array $configuration): void;

    public function setOrder(int $order): void;

    public function process(Context $context): Context;
}
