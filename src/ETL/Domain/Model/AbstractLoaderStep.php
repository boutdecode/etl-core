<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\ETL\Domain\Model;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;

abstract class AbstractLoaderStep implements LoaderStep
{
    protected string $name;

    protected string $code;

    /**
     * @var array<string, mixed>
     */
    protected array $configuration = [];

    protected int $order = 0;

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name ?? $this->code;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    /**
     * @param array<string, mixed> $configuration
     */
    public function setConfiguration(array $configuration): void
    {
        $this->configuration = $configuration;
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    public function setOrder(int $order): void
    {
        $this->order = $order;
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfigurationDescription(): array
    {
        return [];
    }

    public function process(Context $context): Context
    {
        $stepConfig = $context->getConfigurationValue($this->getName(), []);
        /** @var array<string, mixed> $config */
        $config = is_array($stepConfig) ? $stepConfig : [];

        $destination = $config['destination'] ?? $this->configuration['destination'] ?? null;

        $result = $this->load($context->getInput(), $destination, $config);

        return $context->setResult($this->getName(), $result);
    }
}
