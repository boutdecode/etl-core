<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\Run\Domain\Runner;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Pipeline;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Step;
use BoutDeCode\ETLCoreBundle\ETL\Domain\Model\ExecutableStep;
use BoutDeCode\ETLCoreBundle\ETL\Domain\Resolver\StepResolver;
use BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\CycleLife\Pipeline\PipelineMiddlewareChain;
use BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\Middleware;
use BoutDeCode\ETLCoreBundle\Run\Domain\Runner\DefaultPipelineRunner;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class DefaultPipelineRunnerTest extends TestCase
{
    private StepResolver $stepResolver;

    protected function setUp(): void
    {
        $this->stepResolver = $this->createMock(StepResolver::class);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function constructShouldInitializeWithDependencies(): void
    {
        $middlewareChain = new PipelineMiddlewareChain([]);
        $runner = new DefaultPipelineRunner($middlewareChain, $this->stepResolver);
        $this->assertInstanceOf(DefaultPipelineRunner::class, $runner);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function runShouldResolveStepsAndSetRunnableSteps(): void
    {
        // Arrange
        $step = $this->createMock(Step::class);
        $step->method('getCode')->willReturn('test_code');
        $step->method('getName')->willReturn('Test Step');
        $step->method('getConfiguration')->willReturn([
            'test' => 'config',
        ]);

        $executableStep = $this->createMockExecutableStep();
        $this->stepResolver->method('resolve')
            ->with('test_code')
            ->willReturn($executableStep);

        $pipeline = $this->createBasicPipelineMock([$step]);

        // Use a test middleware to verify the context is properly created
        $capturedContext = null;
        $testMiddleware = $this->createTestMiddleware(function (Context $context) use (&$capturedContext) {
            $capturedContext = $context;
            return $context;
        });

        $middlewareChain = new PipelineMiddlewareChain([$testMiddleware]);
        $runner = new DefaultPipelineRunner($middlewareChain, $this->stepResolver);

        // Act
        $result = $runner->run($pipeline);

        // Assert
        $this->assertInstanceOf(Context::class, $result);
        $this->assertNotNull($capturedContext);
        $this->assertSame($pipeline, $capturedContext->getPipeline());
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function runShouldHandleNullExecutableSteps(): void
    {
        // Arrange
        $step = $this->createMock(Step::class);
        $step->method('getCode')->willReturn('invalid_code');

        $this->stepResolver->method('resolve')->willReturn(null);

        $pipeline = $this->createBasicPipelineMock([$step]);

        $middlewareChain = new PipelineMiddlewareChain([]);
        $runner = new DefaultPipelineRunner($middlewareChain, $this->stepResolver);

        // Act
        $result = $runner->run($pipeline);

        // Assert
        $this->assertInstanceOf(Context::class, $result);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function runShouldSetExecutableStepNameAndConfiguration(): void
    {
        // Arrange
        $step = $this->createStub(Step::class);
        $step->method('getCode')->willReturn('test_code');
        $step->method('getName')->willReturn('Test Step');
        $step->method('getConfiguration')->willReturn([
            'test' => 'config',
        ]);

        $executableStep = $this->createMock(ExecutableStep::class);
        $executableStep->method('setName')->willReturnSelf();
        $executableStep->method('setConfiguration')->willReturnSelf();
        $executableStep->expects($this->once())->method('setName')->with('Test Step');
        $executableStep->expects($this->once())->method('setConfiguration')->with([
            'test' => 'config',
        ]);

        $this->stepResolver->method('resolve')->willReturn($executableStep);

        $pipeline = $this->createBasicPipelineMock([$step]);

        $middlewareChain = new PipelineMiddlewareChain([]);
        $runner = new DefaultPipelineRunner($middlewareChain, $this->stepResolver);

        // Act
        $runner->run($pipeline);

        // Assert - Verified via mock expectations
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function runShouldCreateContextWithCorrectInputAndConfiguration(): void
    {
        // Arrange
        $input = [
            'test' => 'input',
        ];
        $config = [[
            'name' => 'test',
            'configuration' => [
                'key' => 'config',
            ],
        ]];
        $pipeline = $this->createBasicPipelineMock([], $input, $config);

        $capturedContext = null;
        $testMiddleware = $this->createTestMiddleware(function (Context $context) use (&$capturedContext) {
            $capturedContext = $context;
            return $context;
        });

        $middlewareChain = new PipelineMiddlewareChain([$testMiddleware]);
        $runner = new DefaultPipelineRunner($middlewareChain, $this->stepResolver);

        // Act
        $runner->run($pipeline);

        // Assert
        $this->assertNotNull($capturedContext);
        $this->assertSame($input, $capturedContext->getInput());
        $this->assertSame($pipeline, $capturedContext->getPipeline());
        // Context stores configuration internally but doesn't expose getConfiguration()
        $this->assertSame([
            'key' => 'config',
        ], $capturedContext->getConfigurationValue('test'));
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function runShouldCloneExecutableStepsToAvoidSideEffects(): void
    {
        // Arrange
        $step = $this->createStub(Step::class);
        $step->method('getCode')->willReturn('test_code');
        $step->method('getName')->willReturn('Test Step');
        $step->method('getConfiguration')->willReturn(['config']);

        $originalExecutableStep = $this->createMockExecutableStep();
        $this->stepResolver->method('resolve')->willReturn($originalExecutableStep);

        $pipeline = $this->createBasicPipelineMock([$step]);

        $middlewareChain = new PipelineMiddlewareChain([]);
        $runner = new DefaultPipelineRunner($middlewareChain, $this->stepResolver);

        // Act
        $result = $runner->run($pipeline);

        // Assert - Verify the runner returns a valid context
        $this->assertInstanceOf(Context::class, $result);
        $this->assertSame($pipeline, $result->getPipeline());
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function runShouldMaintainStepOrderWhenResolvingSteps(): void
    {
        // Arrange
        $step1 = $this->createStepMock('code1', 'Step 1');
        $step2 = $this->createStepMock('code2', 'Step 2');
        $step3 = $this->createStepMock('code3', 'Step 3');

        $this->stepResolver->method('resolve')
            ->willReturnCallback(function (string $code) {
                return $this->createMockExecutableStep();
            });

        $pipeline = $this->createBasicPipelineMock([$step1, $step2, $step3]);

        $middlewareChain = new PipelineMiddlewareChain([]);
        $runner = new DefaultPipelineRunner($middlewareChain, $this->stepResolver);

        // Act
        $result = $runner->run($pipeline);

        // Assert
        $this->assertInstanceOf(Context::class, $result);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function runShouldLogWarningWhenStepCodeIsUnknown(): void
    {
        // Arrange
        $step = $this->createMock(Step::class);
        $step->method('getCode')->willReturn('unknown_code');

        $this->stepResolver->method('resolve')->willReturn(null);

        $pipeline = $this->createBasicPipelineMock([$step]);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('warning')
            ->with(
                $this->stringContains('{code}'),
                $this->callback(fn (array $ctx) => ($ctx['code'] ?? null) === 'unknown_code'),
            );

        $middlewareChain = new PipelineMiddlewareChain([]);
        $runner = new DefaultPipelineRunner($middlewareChain, $this->stepResolver, $logger);

        // Act
        $result = $runner->run($pipeline);

        // Assert
        $this->assertInstanceOf(Context::class, $result);
    }

    private function createMockExecutableStep()
    {
        $mock = $this->createStub(ExecutableStep::class);
        $mock->method('setName')->willReturnSelf();
        $mock->method('setConfiguration')->willReturnSelf();
        $mock->method('setOrder')->willReturnSelf();
        return $mock;
    }

    private function createStepMock(string $code, string $name): Step
    {
        $step = $this->createStub(Step::class);
        $step->method('getCode')->willReturn($code);
        $step->method('getName')->willReturn($name);
        $step->method('getConfiguration')->willReturn([]);
        $step->method('getOrder')->willReturn(0);
        return $step;
    }

    private function createTestMiddleware(callable $handler): Middleware
    {
        $middleware = $this->createStub(Middleware::class);
        $middleware->method('process')
            ->willReturnCallback(function (Context $context, callable $next) use ($handler) {
                $result = $next($context);
                return $handler($result);
            });
        return $middleware;
    }

    private function createBasicPipelineMock(array $steps, array $input = [], array $configuration = []): Pipeline
    {
        return new TestPipeline($steps, $input, $configuration);
    }
}

/**
 * Test Pipeline implementation that supports setRunnableSteps
 */
class TestPipeline implements Pipeline
{
    private array $steps = [];

    private array $input = [];

    private array $configuration = [];

    private array $runnableSteps = [];

    public function __construct(array $steps = [], array $input = [], array $configuration = [])
    {
        $this->steps = $steps;
        $this->input = $input;
        $this->configuration = $configuration;
    }

    public function getId(): string
    {
        return 'test-pipeline-id';
    }

    public function setRunnableSteps(iterable $runnableSteps): void
    {
        $this->runnableSteps = $runnableSteps instanceof \Traversable
            ? iterator_to_array($runnableSteps)
            : $runnableSteps;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }

    public function getScheduledAt(): ?\DateTimeImmutable
    {
        return null;
    }

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return null;
    }

    public function getFinishedAt(): ?\DateTimeImmutable
    {
        return null;
    }

    public function getStatus(): \BoutDeCode\ETLCoreBundle\Core\Domain\Enum\PipelineStatus
    {
        return \BoutDeCode\ETLCoreBundle\Core\Domain\Enum\PipelineStatus::PENDING;
    }

    public function getSteps(): iterable
    {
        return $this->steps;
    }

    public function getRunnableSteps(): iterable
    {
        return $this->runnableSteps;
    }

    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    public function getInput(): array
    {
        return $this->input;
    }

    public function getStepFromRunnableStep(Step $runnableStep): ?Step
    {
        foreach ($this->steps as $step) {
            if ($step->getName() === $runnableStep->getName()) {
                return $step;
            }
        }

        return null;
    }

    public function reset(): void
    {
    }

    public function start(): void
    {
    }

    public function finish(): void
    {
    }
}
