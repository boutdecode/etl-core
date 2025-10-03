<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Core\Domain\Model;

interface Step
{
    public function getName(): ?string;

    public function getCode(): string;

    /**
     * @return array<string, mixed>
     */
    public function getConfiguration(): array;

    public function getOrder(): int;
}
