<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\ETL\Infrastructure\Step\Loader;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\ETL\Domain\Model\AbstractLoaderStep;
use BoutDeCode\ETLCoreBundle\ETL\Infrastructure\Step\Loader\CsvFileLoadStep;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CsvFileLoadStepTest extends TestCase
{
    private CsvFileLoadStep $loadStep;

    private string $tempFilePath;

    private Context $context;

    protected function setUp(): void
    {
        $this->loadStep = new CsvFileLoadStep();
        $this->tempFilePath = sys_get_temp_dir() . '/test_csv_output_' . uniqid() . '.csv';
        $this->context = new Context(null);
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
        $this->assertSame('etl.loader.csv_file', $this->loadStep->getCode());
    }

    #[Test]
    public function stepShouldExtendAbstractLoaderStep(): void
    {
        $this->assertInstanceOf(AbstractLoaderStep::class, $this->loadStep);
    }

    #[Test]
    public function getConfigurationDescriptionShouldReturnExpectedKeys(): void
    {
        $description = $this->loadStep->getConfigurationDescription();

        $this->assertIsArray($description);
        $this->assertArrayHasKey('destination', $description);
        $this->assertArrayHasKey('delimiter', $description);
        $this->assertArrayHasKey('withHeader', $description);
        $this->assertArrayHasKey('enclosure', $description);
        $this->assertArrayHasKey('escape', $description);
    }

    #[Test]
    public function loadWithArrayDataShouldCreateCsvFile(): void
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

        $result = $this->loadStep->load($data, $this->tempFilePath, $this->context);

        $this->assertTrue($result);
        $this->assertFileExists($this->tempFilePath);

        $content = file_get_contents($this->tempFilePath);
        $this->assertStringContainsString('name', $content);
        $this->assertStringContainsString('John', $content);
        $this->assertStringContainsString('Jane', $content);
    }

    #[Test]
    public function loadShouldWriteHeaderRowByDefault(): void
    {
        $data = [
            [
                'name' => 'Alice',
                'city' => 'Paris',
            ],
        ];

        $this->loadStep->load($data, $this->tempFilePath, $this->context);

        $lines = file($this->tempFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $this->assertIsArray($lines);
        $this->assertGreaterThanOrEqual(2, count($lines));
        $this->assertStringContainsString('name', $lines[0]);
        $this->assertStringContainsString('city', $lines[0]);
    }

    #[Test]
    public function loadWithWithHeaderFalseShouldNotWriteHeader(): void
    {
        $data = [
            [
                'name' => 'Alice',
                'city' => 'Paris',
            ],
        ];

        $loadStep = new CsvFileLoadStep(withHeader: false);
        $loadStep->load($data, $this->tempFilePath, $this->context);

        $lines = file($this->tempFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $this->assertIsArray($lines);
        $this->assertCount(1, $lines);
        $this->assertStringNotContainsString('name', $lines[0]);
    }

    #[Test]
    public function loadWithCustomDelimiterShouldWork(): void
    {
        $data = [
            [
                'name' => 'Alice',
                'city' => 'Paris',
            ],
            [
                'name' => 'Bob',
                'city' => 'Lyon',
            ],
        ];

        $result = $this->loadStep->load($data, $this->tempFilePath, $this->context, [
            'delimiter' => ';',
        ]);

        $this->assertTrue($result);
        $content = file_get_contents($this->tempFilePath);
        $this->assertStringContainsString(';', $content);
    }

    #[Test]
    public function loadWithConfigurationShouldOverrideConstructorDefaults(): void
    {
        $loadStep = new CsvFileLoadStep(delimiter: ';');
        $data = [[
            'col1' => 'val1',
            'col2' => 'val2',
        ]];

        $result = $loadStep->load($data, $this->tempFilePath, $this->context, [
            'delimiter' => ',',
        ]);

        $this->assertTrue($result);
        $content = file_get_contents($this->tempFilePath);
        $this->assertStringContainsString(',', $content);
    }

    #[Test]
    public function loadShouldThrowExceptionForNonStringDestination(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('File path must be a string');

        $this->loadStep->load(['data'], 123, $this->context);
    }

    #[Test]
    public function loadShouldThrowExceptionForNullDestination(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('File path must be a string');

        $this->loadStep->load(['data'], null, $this->context);
    }

    #[Test]
    public function loadShouldThrowExceptionForArrayDestination(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('File path must be a string');

        $this->loadStep->load(['data'], ['path'], $this->context);
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

        $this->loadStep->load($initialData, $this->tempFilePath, $this->context);
        $this->assertFileExists($this->tempFilePath);

        $result = $this->loadStep->load($newData, $this->tempFilePath, $this->context);

        $this->assertTrue($result);
        $content = file_get_contents($this->tempFilePath);
        $this->assertStringContainsString('second', $content);
        $this->assertStringNotContainsString('first', $content);
    }

    #[Test]
    public function loadWithNonBoolWithHeaderShouldUseDefault(): void
    {
        $data = [[
            'name' => 'Alice',
        ]];

        $result = $this->loadStep->load($data, $this->tempFilePath, $this->context, [
            'withHeader' => 'not_a_boolean',
        ]);

        $this->assertTrue($result);
        $this->assertFileExists($this->tempFilePath);
    }

    #[Test]
    public function loadWithNonStringDelimiterShouldUseDefault(): void
    {
        $data = [[
            'name' => 'Alice',
        ]];

        $result = $this->loadStep->load($data, $this->tempFilePath, $this->context, [
            'delimiter' => 999,
        ]);

        $this->assertTrue($result);
        $this->assertFileExists($this->tempFilePath);
    }

    #[Test]
    public function constructWithCustomParametersShouldApplyThem(): void
    {
        $loadStep = new CsvFileLoadStep(delimiter: ';', withHeader: true, enclosure: "'", escape: '/');

        $this->assertSame('etl.loader.csv_file', $loadStep->getCode());
    }
}
