<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\DependencyInjection\Compiler;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\DependencyInjection\Compiler\ExecutableStepTagPass;
use BoutDeCode\ETLCoreBundle\ETL\Domain\Attribute\AsExecutableStep;
use BoutDeCode\ETLCoreBundle\ETL\Domain\Model\AbstractExtractorStep;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class ExecutableStepTagPassTest extends TestCase
{
    #[Test]
    public function itTagsExecutableStepServicesWithCodeFromAttribute(): void
    {
        $container = new ContainerBuilder();
        $definition = new Definition(TagPassStepWithAttribute::class);
        $container->setDefinition('test.step.with_attribute', $definition);

        $pass = new ExecutableStepTagPass();
        $pass->process($container);

        $this->assertTrue($definition->hasTag('boutdecode_etl_core.executable_step'));

        $tags = $definition->getTag('boutdecode_etl_core.executable_step');
        $this->assertCount(1, $tags);
        $this->assertSame('etl.test.tag_pass_with_attr', $tags[0]['code']);
    }

    #[Test]
    public function itTagsExecutableStepServicesWithoutCodeWhenNoAttribute(): void
    {
        $container = new ContainerBuilder();
        $definition = new Definition(TagPassStepWithoutAttribute::class);
        $container->setDefinition('test.step.without_attribute', $definition);

        $pass = new ExecutableStepTagPass();
        $pass->process($container);

        $this->assertTrue($definition->hasTag('boutdecode_etl_core.executable_step'));

        $tags = $definition->getTag('boutdecode_etl_core.executable_step');
        $this->assertCount(1, $tags);
        $this->assertArrayNotHasKey('code', $tags[0]);
    }

    #[Test]
    public function itDoesNotTagNonExecutableStepServices(): void
    {
        $container = new ContainerBuilder();
        $definition = new Definition(NotAStep::class);
        $container->setDefinition('test.not_a_step', $definition);

        $pass = new ExecutableStepTagPass();
        $pass->process($container);

        $this->assertFalse($definition->hasTag('boutdecode_etl_core.executable_step'));
    }

    #[Test]
    public function itDoesNotDuplicateTagWhenAlreadyTagged(): void
    {
        $container = new ContainerBuilder();
        $definition = new Definition(TagPassStepWithAttribute::class);
        $definition->addTag('boutdecode_etl_core.executable_step');
        $container->setDefinition('test.step.already_tagged', $definition);

        $pass = new ExecutableStepTagPass();
        $pass->process($container);

        $this->assertCount(1, $definition->getTag('boutdecode_etl_core.executable_step'));
    }

    #[Test]
    public function itSkipsDefinitionsWithNonExistentClass(): void
    {
        $container = new ContainerBuilder();
        $definition = new Definition('NonExistent\\Class\\ThatDoesNotExist');
        $container->setDefinition('test.non_existent', $definition);

        $pass = new ExecutableStepTagPass();

        // Should not throw
        $pass->process($container);

        $this->assertFalse($definition->hasTag('boutdecode_etl_core.executable_step'));
    }
}

// ---------------------------------------------------------------------------
// Fixtures

#[AsExecutableStep(code: 'etl.test.tag_pass_with_attr')]
class TagPassStepWithAttribute extends AbstractExtractorStep
{
    public function extract(mixed $source, Context $context, array $configuration = []): mixed
    {
        return $source;
    }
}

class TagPassStepWithoutAttribute extends AbstractExtractorStep
{
    protected string $code = 'etl.test.tag_pass_no_attr';

    public function extract(mixed $source, Context $context, array $configuration = []): mixed
    {
        return $source;
    }
}

class NotAStep
{
}
