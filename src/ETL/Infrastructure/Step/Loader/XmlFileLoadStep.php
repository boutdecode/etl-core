<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\ETL\Infrastructure\Step\Loader;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\ETL\Domain\Attribute\AsExecutableStep;
use BoutDeCode\ETLCoreBundle\ETL\Domain\Model\AbstractLoaderStep;

#[AsExecutableStep(
    code: 'etl.loader.xml_file',
    configurationDescription: [
        'destination' => 'Path to the XML file to write data into',
        'rootNode' => 'Name of the XML root element (default: "root")',
        'recordNode' => 'Name of the XML element wrapping each record (default: "record")',
        'encoding' => 'XML document encoding (default: "UTF-8")',
        'version' => 'XML document version (default: "1.0")',
    ],
)]
final class XmlFileLoadStep extends AbstractLoaderStep
{
    public function __construct(
        private readonly string $rootNode = 'root',
        private readonly string $recordNode = 'record',
        private readonly string $encoding = 'UTF-8',
        private readonly string $version = '1.0',
    ) {
    }

    /**
     * @param array<string, mixed> $configuration
     */
    public function load(mixed $data, mixed $destination, Context $context, array $configuration = []): bool
    {
        if (! is_string($destination)) {
            throw new \InvalidArgumentException('File path must be a string');
        }

        $rootNode = $configuration['rootNode'] ?? $this->configuration['rootNode'] ?? $this->rootNode;
        $recordNode = $configuration['recordNode'] ?? $this->configuration['recordNode'] ?? $this->recordNode;
        $encoding = $configuration['encoding'] ?? $this->configuration['encoding'] ?? $this->encoding;
        $version = $configuration['version'] ?? $this->configuration['version'] ?? $this->version;

        $rootNodeStr = is_string($rootNode) ? $rootNode : $this->rootNode;
        $recordNodeStr = is_string($recordNode) ? $recordNode : $this->recordNode;
        $encodingStr = is_string($encoding) ? $encoding : $this->encoding;
        $versionStr = is_string($version) ? $version : $this->version;

        $dom = new \DOMDocument($versionStr, $encodingStr);
        $dom->formatOutput = true;

        $root = $dom->createElement($rootNodeStr);
        $dom->appendChild($root);

        /** @var iterable<mixed> $iterableData */
        $iterableData = is_iterable($data) ? $data : [$data];

        foreach ($iterableData as $row) {
            /** @var array<string, mixed> $rowArray */
            $rowArray = is_array($row) ? $row : [];
            $recordElement = $dom->createElement($recordNodeStr);
            $this->appendFields($dom, $recordElement, $rowArray);
            $root->appendChild($recordElement);
        }

        $xmlContent = $dom->saveXML();
        if ($xmlContent === false) {
            throw new \RuntimeException("Failed to generate XML content for: {$destination}");
        }

        if (file_put_contents($destination, $xmlContent) === false) {
            throw new \RuntimeException("Failed to write XML file: {$destination}");
        }

        return true;
    }

    /**
     * @param array<string, mixed> $fields
     */
    private function appendFields(\DOMDocument $dom, \DOMElement $parent, array $fields): void
    {
        foreach ($fields as $key => $value) {
            $elementName = $this->sanitizeElementName((string) $key);
            if (is_array($value)) {
                $child = $dom->createElement($elementName);
                /** @var array<string, mixed> $value */
                $this->appendFields($dom, $child, $value);
                $parent->appendChild($child);
            } else {
                $child = $dom->createElement($elementName);
                $textValue = is_scalar($value) || $value === null ? (string) $value : '';
                $child->appendChild($dom->createTextNode($textValue));
                $parent->appendChild($child);
            }
        }
    }

    private function sanitizeElementName(string $name): string
    {
        // Replace characters that are invalid in XML element names with underscores
        $sanitized = preg_replace('/[^a-zA-Z0-9_\-.]/', '_', $name) ?? $name;

        // Ensure the element name does not start with a digit or hyphen
        if ($sanitized !== '' && (ctype_digit($sanitized[0]) || $sanitized[0] === '-')) {
            $sanitized = '_' . $sanitized;
        }

        return $sanitized !== '' ? $sanitized : '_';
    }
}
