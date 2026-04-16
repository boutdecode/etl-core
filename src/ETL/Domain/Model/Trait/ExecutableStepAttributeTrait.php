<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\ETL\Domain\Model\Trait;

use BoutDeCode\ETLCoreBundle\ETL\Domain\Attribute\AsExecutableStep;

/**
 * Provides default implementations of {@see \BoutDeCode\ETLCoreBundle\ETL\Domain\Model\ExecutableStep}
 * metadata methods by reading the {@see AsExecutableStep} PHP attribute placed on the concrete class.
 *
 * When the attribute is present:
 *  - {@see getCode()}                    returns {@see AsExecutableStep::$code}
 *  - {@see getConfigurationDescription()} returns {@see AsExecutableStep::$configurationDescription}
 *
 * When the attribute is absent the trait falls back to the `$code` protected property
 * (for backward compatibility) and returns an empty array for the configuration description.
 */
trait ExecutableStepAttributeTrait
{
    protected ?string $name = null;

    protected string $code = '';

    /**
     * @var array<string, mixed>
     */
    protected array $configuration = [];

    protected int $order = 0;

    public function getCode(): string
    {
        $attribute = $this->resolveAttribute();

        return $attribute !== null ? $attribute->code : $this->code;
    }

    public function getName(): string
    {
        return $this->name ?? $this->getCode();
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
        $attribute = $this->resolveAttribute();

        return $attribute !== null ? $attribute->configurationDescription : [];
    }

    private function resolveAttribute(): ?AsExecutableStep
    {
        /** @var array<class-string, AsExecutableStep|null> $cache */
        static $cache = [];

        $class = static::class;

        if (array_key_exists($class, $cache)) {
            return $cache[$class];
        }

        $attributes = (new \ReflectionClass($class))->getAttributes(AsExecutableStep::class);

        if ($attributes === []) {
            return $cache[$class] = null;
        }

        /** @var AsExecutableStep $instance */
        $instance = $attributes[0]->newInstance();

        return $cache[$class] = $instance;
    }
}
