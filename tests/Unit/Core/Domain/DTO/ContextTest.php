<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\Core\Domain\DTO;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Pipeline;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Step;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ContextTest extends TestCase
{
    #[Test]
    public function constructWithInitialInputShouldCreateContext(): void
    {
        $initialInput = [
            'key' => 'value',
        ];

        $context = new Context($initialInput);

        $this->assertSame($initialInput, $context->getInput());
    }

    #[Test]
    public function constructWithAllParametersShouldCreateContextWithValues(): void
    {
        $initialInput = 'initial';
        $resultSet = [
            'result1' => 'value1',
        ];
        $inputSet = [
            'input1' => 'inputValue1',
        ];
        $configuration = [
            'config1' => 'configValue',
        ];

        $context = new Context($initialInput, $resultSet, $inputSet, $configuration);

        $this->assertSame($initialInput, $context->getInput());
        $this->assertSame('configValue', $context->getConfigurationValue('config1'));
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function setPipelineShouldSetPipelineAndReturnSelf(): void
    {
        $context = new Context('input');
        $pipeline = $this->createMock(Pipeline::class);

        $result = $context->setPipeline($pipeline);

        $this->assertSame($context, $result);
        $this->assertSame($pipeline, $context->getPipeline());
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function setCurrentStepShouldSetStepAndReturnSelf(): void
    {
        $context = new Context('input');
        $step = $this->createMock(Step::class);

        $result = $context->setCurrentStep($step);

        $this->assertSame($context, $result);
        $this->assertSame($step, $context->getCurrentStep());
    }

    #[Test]
    public function setCurrentStepWithNullShouldSetStepToNull(): void
    {
        $context = new Context('input');

        $result = $context->setCurrentStep(null);

        $this->assertSame($context, $result);
        $this->assertNull($context->getCurrentStep());
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function setResultShouldUpdateInputAndResultAndReturnSelf(): void
    {
        $context = new Context('initial');
        $step = $this->createMock(Step::class);
        $step->method('getCode')->willReturn('step1');
        $step->method('getName')->willReturn('step1');
        $context->setCurrentStep($step);
        $resultValue = [
            'processed' => 'data',
        ];

        $result = $context->setResult('step1', $resultValue);

        $this->assertSame($context, $result);
        $this->assertSame($resultValue, $context->getInput());
        $this->assertSame($resultValue, $context->getResult());
        $this->assertSame($resultValue, $context->getResultSet());
        $this->assertSame('initial', $context->getInputSet());
    }

    #[Test]
    public function getInputShouldReturnCurrentInputOrInitialInput(): void
    {
        $initialInput = 'initial';
        $context = new Context($initialInput);

        // Before setting result, should return initial input
        $this->assertSame($initialInput, $context->getInput());

        // After setting result, should return the result as new input
        $newInput = [
            'new' => 'input',
        ];
        $context->setResult('step1', $newInput);

        $this->assertSame($newInput, $context->getInput());
    }

    #[Test]
    public function getConfigurationValueShouldReturnValueOrDefault(): void
    {
        $configuration = [
            'existingKey' => 'existingValue',
        ];
        $context = new Context('input', [], [], $configuration);

        $this->assertSame('existingValue', $context->getConfigurationValue('existingKey'));
        $this->assertSame('defaultValue', $context->getConfigurationValue('nonExistentKey', 'defaultValue'));
        $this->assertNull($context->getConfigurationValue('nonExistentKey'));
    }

    #[Test]
    public function setConfigurationValueShouldSetValueAndReturnSelf(): void
    {
        $context = new Context('input');

        $result = $context->setConfigurationValue('newKey', 'newValue');

        $this->assertSame($context, $result);
        $this->assertSame('newValue', $context->getConfigurationValue('newKey'));
    }

    #[Test]
    public function fromNoExecutionShouldCreateContextWithEmptyInput(): void
    {
        $context = Context::fromNoExecution();

        $this->assertInstanceOf(Context::class, $context);
        $this->assertSame('', $context->getInput());
        $this->assertNull($context->getResultSet());
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function multipleResultsShouldAccumulateInResultSet(): void
    {
        $context = new Context('initial');
        $step1 = $this->createMock(Step::class);
        $step1->method('getCode')->willReturn('step1');
        $step1->method('getName')->willReturn('step1');
        $step2 = $this->createMock(Step::class);
        $step2->method('getCode')->willReturn('step2');
        $step2->method('getName')->willReturn('step2');

        $context->setCurrentStep($step1);
        $context->setResult('step1', 'result1');
        $context->setCurrentStep($step2);
        $context->setResult('step2', 'result2');

        $this->assertSame('result2', $context->getResultSet()); // Should be current step result
        $this->assertSame('result2', $context->getResult()); // Should be last result
        $this->assertSame('result2', $context->getInput()); // Input should be last result
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function getInputSetShouldReturnInputForCurrentStep(): void
    {
        $context = new Context('initial');
        $step = $this->createMock(Step::class);
        $step->method('getCode')->willReturn('step1');
        $step->method('getName')->willReturn('step1');
        $context->setCurrentStep($step);

        $context->setResult('step1', 'result1');

        $this->assertSame('initial', $context->getInputSet());
    }

    #[Test]
    public function getInitialInputShouldReturnInitialInput(): void
    {
        $initialInput = [
            'initial' => 'data',
        ];
        $context = new Context($initialInput);

        $this->assertSame($initialInput, $context->getInitialInput());

        // Should still return initial input even after setting results
        $context->setResult('step1', 'new_result');
        $this->assertSame($initialInput, $context->getInitialInput());
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function getResultSetShouldReturnResultForCurrentStep(): void
    {
        $context = new Context('initial');
        $step = $this->createMock(Step::class);
        $step->method('getCode')->willReturn('step1');
        $step->method('getName')->willReturn('step1');
        $context->setCurrentStep($step);

        $resultValue = [
            'test' => 'value',
        ];
        $context->setResult('step1', $resultValue);

        $this->assertSame($resultValue, $context->getResultSet());
    }

    #[Test]
    public function getResultSetWithoutCurrentStepShouldReturnNull(): void
    {
        $context = new Context('initial');

        $this->assertNull($context->getResultSet());
    }

    #[Test]
    public function getInputSetWithoutCurrentStepShouldReturnNull(): void
    {
        $context = new Context('initial');

        $this->assertNull($context->getInputSet());
    }
}
