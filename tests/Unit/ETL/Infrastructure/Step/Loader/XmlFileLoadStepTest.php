<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\ETL\Infrastructure\Step\Loader;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\ETL\Domain\Model\AbstractLoaderStep;
use BoutDeCode\ETLCoreBundle\ETL\Infrastructure\Step\Loader\XmlFileLoadStep;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class XmlFileLoadStepTest extends TestCase
{
    private XmlFileLoadStep $loadStep;

    private string $tempFilePath;

    private Context $context;

    protected function setUp(): void
    {
        $this->loadStep = new XmlFileLoadStep();
        $this->tempFilePath = sys_get_temp_dir() . '/test_xml_output_' . uniqid() . '.xml';
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
        $this->assertSame('etl.loader.xml_file', $this->loadStep->getCode());
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
        $this->assertArrayHasKey('rootNode', $description);
        $this->assertArrayHasKey('recordNode', $description);
        $this->assertArrayHasKey('encoding', $description);
        $this->assertArrayHasKey('version', $description);
    }

    #[Test]
    public function loadWithArrayDataShouldCreateXmlFile(): void
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
        $this->assertStringContainsString('<root>', $content);
        $this->assertStringContainsString('<record>', $content);
        $this->assertStringContainsString('<name>John</name>', $content);
        $this->assertStringContainsString('<name>Jane</name>', $content);
        $this->assertStringContainsString('<age>30</age>', $content);
    }

    #[Test]
    public function loadShouldWriteValidXml(): void
    {
        $data = [
            [
                'name' => 'Alice',
                'city' => 'Paris',
            ],
        ];

        $this->loadStep->load($data, $this->tempFilePath, $this->context);

        $content = file_get_contents($this->tempFilePath);
        $xml = simplexml_load_string($content);
        $this->assertInstanceOf(\SimpleXMLElement::class, $xml);
    }

    #[Test]
    public function loadShouldWriteXmlDeclaration(): void
    {
        $data = [[
            'key' => 'value',
        ]];

        $this->loadStep->load($data, $this->tempFilePath, $this->context);

        $content = file_get_contents($this->tempFilePath);
        $this->assertStringContainsString('<?xml', $content);
        $this->assertStringContainsString('UTF-8', $content);
    }

    #[Test]
    public function loadWithCustomRootNodeShouldUseIt(): void
    {
        $loadStep = new XmlFileLoadStep(rootNode: 'items');
        $data = [[
            'id' => 1,
        ]];

        $loadStep->load($data, $this->tempFilePath, $this->context);

        $content = file_get_contents($this->tempFilePath);
        $this->assertStringContainsString('<items>', $content);
        $this->assertStringNotContainsString('<root>', $content);
    }

    #[Test]
    public function loadWithCustomRecordNodeShouldUseIt(): void
    {
        $loadStep = new XmlFileLoadStep(recordNode: 'user');
        $data = [[
            'name' => 'Alice',
        ]];

        $loadStep->load($data, $this->tempFilePath, $this->context);

        $content = file_get_contents($this->tempFilePath);
        $this->assertStringContainsString('<user>', $content);
        $this->assertStringNotContainsString('<record>', $content);
    }

    #[Test]
    public function loadWithConfigurationRootNodeShouldOverrideConstructorDefault(): void
    {
        $data = [[
            'id' => 1,
        ]];

        $this->loadStep->load($data, $this->tempFilePath, $this->context, [
            'rootNode' => 'catalog',
        ]);

        $content = file_get_contents($this->tempFilePath);
        $this->assertStringContainsString('<catalog>', $content);
    }

    #[Test]
    public function loadWithConfigurationRecordNodeShouldOverrideConstructorDefault(): void
    {
        $data = [[
            'id' => 1,
        ]];

        $this->loadStep->load($data, $this->tempFilePath, $this->context, [
            'recordNode' => 'product',
        ]);

        $content = file_get_contents($this->tempFilePath);
        $this->assertStringContainsString('<product>', $content);
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
            'first' => 'initial',
        ]];
        $newData = [[
            'second' => 'new',
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
    public function loadWithNestedArrayFieldShouldCreateNestedXml(): void
    {
        $data = [
            [
                'name' => 'Alice',
                'address' => [
                    'city' => 'Paris',
                    'zip' => '75001',
                ],
            ],
        ];

        $this->loadStep->load($data, $this->tempFilePath, $this->context);

        $content = file_get_contents($this->tempFilePath);
        $this->assertStringContainsString('<address>', $content);
        $this->assertStringContainsString('<city>Paris</city>', $content);
        $this->assertStringContainsString('<zip>75001</zip>', $content);
    }

    #[Test]
    public function loadWithCustomEncodingShouldUseIt(): void
    {
        $loadStep = new XmlFileLoadStep(encoding: 'ISO-8859-1');
        $data = [[
            'key' => 'value',
        ]];

        $loadStep->load($data, $this->tempFilePath, $this->context);

        $content = file_get_contents($this->tempFilePath);
        $this->assertStringContainsString('ISO-8859-1', $content);
    }

    #[Test]
    public function loadWithNonStringRootNodeShouldUseDefault(): void
    {
        $data = [[
            'key' => 'value',
        ]];

        $result = $this->loadStep->load($data, $this->tempFilePath, $this->context, [
            'rootNode' => 999,
        ]);

        $this->assertTrue($result);
        $content = file_get_contents($this->tempFilePath);
        $this->assertStringContainsString('<root>', $content);
    }

    #[Test]
    public function loadWithNonStringRecordNodeShouldUseDefault(): void
    {
        $data = [[
            'key' => 'value',
        ]];

        $result = $this->loadStep->load($data, $this->tempFilePath, $this->context, [
            'recordNode' => 999,
        ]);

        $this->assertTrue($result);
        $content = file_get_contents($this->tempFilePath);
        $this->assertStringContainsString('<record>', $content);
    }

    #[Test]
    public function constructWithCustomParametersShouldApplyThem(): void
    {
        $loadStep = new XmlFileLoadStep(
            rootNode: 'catalog',
            recordNode: 'product',
            encoding: 'UTF-16',
            version: '1.1',
        );

        $this->assertSame('etl.loader.xml_file', $loadStep->getCode());
    }
}
