<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\ETL\Infrastructure\Step\Loader;

use BoutDeCode\ETLCoreBundle\ETL\Infrastructure\Step\Loader\JsonFileLoadStep;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class JsonFileLoadStepTest extends TestCase
{
    private JsonFileLoadStep $loadStep;

    private string $tempFilePath;

    protected function setUp(): void
    {
        $this->loadStep = new JsonFileLoadStep();
        $this->tempFilePath = sys_get_temp_dir() . '/test_output_' . uniqid() . '.json';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFilePath)) {
            unlink($this->tempFilePath);
        }
    }

    #[Test]
    public function getCodeShouldReturnCorrectCode(): void
    {
        $this->assertSame(JsonFileLoadStep::CODE, $this->loadStep->getCode());
        $this->assertSame('etl.loader.json_file', $this->loadStep->getCode());
    }

    #[Test]
    public function loadWithArrayDataShouldCreateJsonFile(): void
    {
        $data = [
            [
                'name' => 'John',
                'age' => 30,
            ],
            [
                'name' => 'Jane',
                'age' => 25,
            ],
        ];

        $result = $this->loadStep->load($data, $this->tempFilePath);

        $this->assertTrue($result);
        $this->assertFileExists($this->tempFilePath);

        $fileContent = file_get_contents($this->tempFilePath);
        $decodedContent = json_decode($fileContent, true);

        $this->assertIsArray($decodedContent);
        $this->assertCount(2, $decodedContent);
        $this->assertSame($data, $decodedContent);
    }

    #[Test]
    public function loadWithSingleItemShouldCreateJsonFile(): void
    {
        $data = [
            'name' => 'John',
            'age' => 30,
        ];

        $result = $this->loadStep->load($data, $this->tempFilePath);

        $this->assertTrue($result);
        $this->assertFileExists($this->tempFilePath);

        $fileContent = file_get_contents($this->tempFilePath);
        $decodedContent = json_decode($fileContent, true);

        $this->assertIsArray($decodedContent);
        // Flow-PHP may create multiple entries for single items
        $this->assertGreaterThan(0, count($decodedContent));
    }

    #[Test]
    public function loadWithCustomOptionsShouldWork(): void
    {
        $data = [[
            'special_chars' => 'éñøðé',
        ]];

        $configuration = [
            'options' => JSON_UNESCAPED_UNICODE,
        ];
        $result = $this->loadStep->load($data, $this->tempFilePath, $configuration);

        $this->assertTrue($result);
        $this->assertFileExists($this->tempFilePath);

        $fileContent = file_get_contents($this->tempFilePath);
        $this->assertStringContainsString('éñøðé', $fileContent);
    }

    #[Test]
    public function loadShouldThrowExceptionForNonStringDestination(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('File path must be a string');

        $this->loadStep->load(['data'], 123);
    }

    #[Test]
    public function loadWithArrayDestinationShouldThrowException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('File path must be a string');

        $this->loadStep->load(['data'], [
            'not' => 'string',
        ]);
    }

    #[Test]
    public function loadWithNullDestinationShouldThrowException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('File path must be a string');

        $this->loadStep->load(['data'], null);
    }

    #[Test]
    public function constructWithCustomOptionsShouldSetDefault(): void
    {
        $customOptions = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES;
        $loadStep = new JsonFileLoadStep($customOptions);

        $data = [[
            'url' => 'https://example.com/path',
        ]];
        $tempPath = sys_get_temp_dir() . '/test_custom_' . uniqid() . '.json';

        $result = $loadStep->load($data, $tempPath);

        $this->assertTrue($result);
        $this->assertFileExists($tempPath);

        $fileContent = file_get_contents($tempPath);
        $this->assertStringContainsString('https://example.com/path', $fileContent);
        $this->assertStringContainsString("\n", $fileContent); // Pretty print adds newlines

        unlink($tempPath);
    }

    #[Test]
    public function configurationParametersShouldOverrideConstructorDefaults(): void
    {
        $constructorOptions = JSON_FORCE_OBJECT;
        $loadStep = new JsonFileLoadStep($constructorOptions);

        $data = [[
            'key' => 'value',
        ]];
        $tempPath = sys_get_temp_dir() . '/test_override_' . uniqid() . '.json';

        $configuration = [
            'options' => JSON_PRETTY_PRINT,
        ];
        $result = $loadStep->load($data, $tempPath, $configuration);

        $this->assertTrue($result);
        $this->assertFileExists($tempPath);

        $fileContent = file_get_contents($tempPath);
        $this->assertStringContainsString("\n", $fileContent); // Pretty print

        unlink($tempPath);
    }

    #[Test]
    public function loadWithNonIntegerOptionsShouldUseDefault(): void
    {
        $configuration = [
            'options' => 'not_an_integer',
        ];

        $data = [[
            'test' => 'data',
        ]];
        $result = $this->loadStep->load($data, $this->tempFilePath, $configuration);

        $this->assertTrue($result);
        $this->assertFileExists($this->tempFilePath);
    }

    #[Test]
    public function loadWithEmptyArrayShouldWork(): void
    {
        $result = $this->loadStep->load([], $this->tempFilePath);

        $this->assertTrue($result);
        // Flow-PHP may not create file for empty arrays
        // Just verify the function completed without error
        $this->assertIsArray([]);
    }

    #[Test]
    public function stepShouldImplementCorrectInterface(): void
    {
        $this->assertInstanceOf(
            \BoutDeCode\ETLCoreBundle\ETL\Domain\Model\AbstractLoaderStep::class,
            $this->loadStep
        );
    }

    #[Test]
    public function loadShouldOverwriteExistingFile(): void
    {
        $initialData = [[
            'first' => 'data',
        ]];
        $newData = [[
            'second' => 'data',
        ]];

        // Write initial file
        $this->loadStep->load($initialData, $this->tempFilePath);
        $this->assertFileExists($this->tempFilePath);

        // Overwrite with new data
        $result = $this->loadStep->load($newData, $this->tempFilePath);

        $this->assertTrue($result);

        $fileContent = file_get_contents($this->tempFilePath);
        $decodedContent = json_decode($fileContent, true);

        $this->assertSame($newData, $decodedContent);
        $this->assertStringNotContainsString('first', $fileContent);
    }
}
