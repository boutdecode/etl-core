<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Core\Domain\DTO;

use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Pipeline;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Step;

class Context
{
    protected mixed $input = null;

    protected mixed $result = null;

    protected ?Pipeline $pipeline = null;

    protected ?Step $currentStep = null;

    /**
     * @param array<string, mixed> $resultSet
     * @param array<string, mixed> $inputSet
     * @param array<string, mixed> $configuration
     */
    public function __construct(
        private readonly mixed $initialInput,
        private array $resultSet = [],
        private array $inputSet = [],
        private array $configuration = []
    ) {
    }

    public function setPipeline(Pipeline $pipeline): self
    {
        $this->pipeline = $pipeline;

        return $this;
    }

    public function getPipeline(): ?Pipeline
    {
        return $this->pipeline;
    }

    public function setCurrentStep(?Step $currentStep): self
    {
        $this->currentStep = $currentStep;

        return $this;
    }

    public function getCurrentStep(): ?Step
    {
        return $this->currentStep;
    }

    public function getInput(): mixed
    {
        return $this->input ?? $this->initialInput;
    }

    public function setResult(string $key, mixed $result): self
    {
        $this->inputSet[$key] = $this->getInput();
        $this->resultSet[$key] = $result;
        $this->input = $result;
        $this->result = $result;

        return $this;
    }

    public function getResultSet(): mixed
    {
        return $this->resultSet[$this->getCurrentStep()?->getName()] ?? null;
    }

    public function getInputSet(): mixed
    {
        return $this->inputSet[$this->getCurrentStep()?->getName()] ?? null;
    }

    public function getInitialInput(): mixed
    {
        return $this->initialInput;
    }

    public function getResult(): mixed
    {
        return $this->result;
    }

    public function getResultByKey(string $key): mixed
    {
        return $this->resultSet[$key] ?? null;
    }

    public function getConfigurationValue(string $key, mixed $default = null): mixed
    {
        return $this->configuration[$key] ?? $default;
    }

    public function setConfigurationValue(string $key, mixed $value): self
    {
        $this->configuration[$key] = $value;

        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function getErrors(): array
    {
        $errors = [];
        foreach ($this->resultSet as $key => $value) {
            if (is_array($value) && isset($value['error']) && is_string($value['error'])) {
                $errors[$key] = $value['error'];
            }
        }

        return $errors;
    }

    public static function fromNoExecution(): self
    {
        return new self('');
    }
}
