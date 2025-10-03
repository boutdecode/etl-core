<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\ETL\Infrastructure\Step\Extractor;

use BoutDeCode\ETLCoreBundle\ETL\Domain\Model\AbstractExtractorStep;

class XmlFileExtractStep extends AbstractExtractorStep
{
    public const string CODE = 'etl.extractor.xml_file';

    protected string $code = self::CODE;

    public function __construct(
        private readonly string $rootNode = 'root',
        private readonly string $recordNode = 'record',
        private readonly bool $useAttributes = true,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfigurationDescription(): array
    {
        return [
            'source' => 'Path to the XML file to extract data from',
            'rootNode' => 'The root node of the XML document (default: "root")',
            'recordNode' => 'The node that represents a single record (default: "record")',
            'useAttributes' => 'Whether to include XML attributes in the extracted data (default: true)',
        ];
    }

    /**
     * @return array<mixed>
     */
    public function extract(mixed $source, array $configuration = []): array
    {
        if (is_array($source)) {
            $source = $source['source'] ?? null;
        }

        if (! is_string($source)) {
            throw new \InvalidArgumentException('Source must be a string representing the file path.');
        }

        if (! file_exists($source)) {
            throw new \InvalidArgumentException("File not found: {$source}");
        }

        $recordNode = $configuration['recordNode'] ?? $this->configuration['recordNode'] ?? $this->recordNode;
        $useAttributes = $configuration['useAttributes'] ?? $this->configuration['useAttributes'] ?? $this->useAttributes;

        $recordNodeStr = is_string($recordNode) ? $recordNode : $this->recordNode;
        $useAttributesBool = is_bool($useAttributes) ? $useAttributes : $this->useAttributes;

        // Use SimpleXML for basic XML parsing
        $xmlContent = file_get_contents($source);
        if ($xmlContent === false) {
            throw new \RuntimeException("Failed to read XML file: {$source}");
        }

        $xml = simplexml_load_string($xmlContent);
        if ($xml === false) {
            throw new \InvalidArgumentException("Invalid XML format in file: {$source}");
        }

        $result = [];

        // Extract records based on recordNode
        if ($recordNodeStr && isset($xml->{$recordNodeStr})) {
            $records = $xml->{$recordNodeStr};
            if ($records instanceof \SimpleXMLElement) {
                foreach ($records as $record) {
                    /** @var \SimpleXMLElement $record */
                    $result[] = $this->xmlToArray($record, $useAttributesBool);
                }
            }
        } else {
            // If no specific record node, convert entire XML
            $result[] = $this->xmlToArray($xml, $useAttributesBool);
        }

        return $result;
    }

    /**
     * @return array<mixed>
     */
    private function xmlToArray(\SimpleXMLElement $xml, bool $useAttributes): array
    {
        $result = [];

        // Include attributes if requested
        if ($useAttributes) {
            foreach ($xml->attributes() as $key => $value) {
                $result['@' . $key] = (string) $value;
            }
        }

        // Convert child elements
        foreach ($xml->children() as $child) {
            $name = $child->getName();
            $siblings = $xml->{$name};
            $siblingCount = $siblings instanceof \SimpleXMLElement ? count($siblings) : 0;
            if ($siblingCount > 1) {
                // Multiple elements with same name - create array
                if (! isset($result[$name])) {
                    $result[$name] = [];
                }
                /** @var array<mixed> $existingArray */
                $existingArray = is_array($result[$name]) ? $result[$name] : [];
                $existingArray[] = $this->xmlToArray($child, $useAttributes);
                $result[$name] = $existingArray;
            } else {
                // Single element
                if (count($child->children()) > 0) {
                    $result[$name] = $this->xmlToArray($child, $useAttributes);
                } else {
                    $result[$name] = (string) $child;
                }
            }
        }

        // If no children and no attributes, return array with text content
        if (empty($result)) {
            $textContent = (string) $xml;
            return $textContent !== '' ? [
                'value' => $textContent,
            ] : [];
        }

        return $result;
    }
}
