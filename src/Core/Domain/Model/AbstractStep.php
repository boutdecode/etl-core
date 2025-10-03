<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Core\Domain\Model;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;

abstract class AbstractStep implements Step
{
    protected ?string $name;

    protected string $code;

    /**
     * @var array<string, mixed>
     */
    protected array $configuration = [];

    protected int $order = 0;

    public function getName(): string
    {
        return $this->name ?? $this->code;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    public function process(Context $context): Context
    {
        return $context;
    }

    public function getOrder(): int
    {
        return $this->order;
    }
}
