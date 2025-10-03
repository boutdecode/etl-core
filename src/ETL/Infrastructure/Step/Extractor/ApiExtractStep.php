<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\ETL\Infrastructure\Step\Extractor;

use BoutDeCode\ETLCoreBundle\ETL\Domain\Model\AbstractExtractorStep;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ApiExtractStep extends AbstractExtractorStep
{
    public const string CODE = 'etl.extractor.api';

    protected string $code = self::CODE;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        /**
         * @var array<string, mixed>
         */
        private readonly array $defaultHeaders = [],
        private readonly int $timeout = 30,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfigurationDescription(): array
    {
        return [
            'method' => 'HTTP method to use (GET, POST, etc.)',
            'headers' => 'Optional HTTP headers to include in the request',
            'body' => 'Optional request body for POST/PUT requests',
            'timeout' => 'Request timeout in seconds',
            'responseFormat' => 'Expected response format (json, xml, csv, text)',
        ];
    }

    /**
     * @return array<mixed>
     */
    public function extract(mixed $source, array $configuration = []): array
    {
        if (is_array($source)) {
            $url = $source['url'] ?? null;
        } else {
            $url = $source;
        }

        if (! is_string($url)) {
            throw new \InvalidArgumentException('Source must be a string representing the API URL.');
        }

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException("Invalid URL: {$url}");
        }

        $method = $configuration['method'] ?? $this->configuration['method'] ?? 'GET';
        /** @var array<string, mixed> $configHeaders */
        $configHeaders = $this->configuration['headers'] ?? [];
        /** @var array<string, mixed> $requestHeaders */
        $requestHeaders = $configuration['headers'] ?? [];
        $headers = array_merge(
            $this->defaultHeaders,
            $configHeaders,
            $requestHeaders
        );
        $body = $configuration['body'] ?? $this->configuration['body'] ?? null;
        $timeout = $configuration['timeout'] ?? $this->configuration['timeout'] ?? $this->timeout;
        $responseFormat = $configuration['responseFormat'] ?? $this->configuration['responseFormat'] ?? 'json';

        $options = [
            'headers' => $headers,
            'timeout' => is_int($timeout) ? $timeout : $this->timeout,
        ];

        if ($body !== null) {
            if (is_array($body)) {
                $options['json'] = $body;
            } else {
                $options['body'] = $body;
            }
        }

        try {
            $methodStr = is_string($method) ? $method : 'GET';
            $responseFormatStr = is_string($responseFormat) ? $responseFormat : 'json';
            $response = $this->httpClient->request($methodStr, $url, $options);
            $content = $response->getContent();

            return match (strtolower($responseFormatStr)) {
                'json' => $this->parseJsonResponse($content),
                'xml' => $this->parseXmlResponse($content),
                'csv' => $this->parseCsvResponse($content),
                'text', 'plain' => [[
                    'content' => $content,
                ]],
                default => throw new \InvalidArgumentException("Unsupported response format: {$responseFormatStr}")
            };
        } catch (\Exception $e) {
            throw new \RuntimeException("API request failed: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * @return array<mixed>
     */
    private function parseJsonResponse(string $content): array
    {
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON response: ' . json_last_error_msg());
        }

        // Ensure we return an array of records
        if (! is_array($data)) {
            return [[
                'value' => $data,
            ]];
        }

        // If it's a simple array, wrap each element
        if (array_is_list($data)) {
            return array_map(fn ($item) => is_array($item) ? $item : [
                'value' => $item,
            ], $data);
        }

        // Single associative array, wrap it
        return [$data];
    }

    /**
     * @return array<mixed>
     */
    private function parseXmlResponse(string $content): array
    {
        $xml = simplexml_load_string($content);
        if ($xml === false) {
            throw new \RuntimeException('Invalid XML response');
        }

        return [$this->xmlToArray($xml)];
    }

    /**
     * @return array<mixed>
     */
    private function parseCsvResponse(string $content): array
    {
        $trimmed = trim($content);
        if ($trimmed === '') {
            return [];
        }

        $lines = array_map('str_getcsv', explode("\n", $trimmed));
        $header = array_shift($lines);

        /** @var array<string> $typedHeader */
        $typedHeader = $header;
        $result = [];

        foreach ($lines as $line) {
            if (count($line) === count($typedHeader)) {
                $result[] = array_combine($typedHeader, $line);
            }
        }

        return $result;
    }

    /**
     * @return array<mixed>
     */
    private function xmlToArray(\SimpleXMLElement $xml): array
    {
        $result = [];

        foreach ($xml->attributes() as $key => $value) {
            $result['@' . $key] = (string) $value;
        }

        foreach ($xml->children() as $child) {
            $name = $child->getName();
            $childArray = $this->xmlToArray($child);

            if (isset($result[$name])) {
                if (! is_array($result[$name]) || ! array_is_list($result[$name])) {
                    $result[$name] = [$result[$name]];
                }
                $result[$name][] = $childArray;
            } else {
                $result[$name] = $childArray;
            }
        }

        if (empty($result)) {
            return [
                'value' => (string) $xml,
            ];
        }

        return $result;
    }
}
