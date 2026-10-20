<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Integration\Run\Infrastructure\Console;

use BoutDeCode\ETLCoreBundle\Run\Infrastructure\Console\WorkflowFileLoader;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class WorkflowFileLoaderTest extends TestCase
{
    /**
     * @var string[]
     */
    private array $files = [];

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            @unlink($file);
        }
    }

    #[Test]
    public function loadShouldParseJson(): void
    {
        $path = $this->createFile('json', '{"name":"wf","configuration":{"a":1}}');

        $this->assertSame([
            'name' => 'wf',
            'configuration' => [
                'a' => 1,
            ],
        ], (new WorkflowFileLoader())->load($path));
    }

    #[Test]
    public function loadShouldParseYaml(): void
    {
        $path = $this->createFile('yaml', "name: wf\nstep_configuration:\n  - { code: a, order: 1 }\n");

        $this->assertSame(
            [
                'name' => 'wf',
                'step_configuration' => [[
                    'code' => 'a',
                    'order' => 1,
                ]],
            ],
            (new WorkflowFileLoader())->load($path),
        );
    }

    #[Test]
    public function loadShouldRejectUnknownExtension(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new WorkflowFileLoader())->load($this->createFile('txt', 'name: wf'));
    }

    #[Test]
    public function loadShouldRejectInvalidJson(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new WorkflowFileLoader())->load($this->createFile('json', '{nope'));
    }

    #[Test]
    public function loadShouldRejectScalarContent(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new WorkflowFileLoader())->load($this->createFile('yaml', 'just a string'));
    }

    #[Test]
    public function loadShouldRejectMissingFile(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new WorkflowFileLoader())->load('/nonexistent/workflow.yaml');
    }

    private function createFile(string $extension, string $content): string
    {
        $path = sys_get_temp_dir() . '/' . uniqid('wf_', true) . '.' . $extension;
        file_put_contents($path, $content);
        $this->files[] = $path;

        return $path;
    }
}
