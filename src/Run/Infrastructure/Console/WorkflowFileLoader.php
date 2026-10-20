<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Run\Infrastructure\Console;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class WorkflowFileLoader
{
    /**
     * @return array<string, mixed>
     */
    public function load(string $path): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new \InvalidArgumentException(sprintf('File "%s" does not exist or is not readable.', $path));
        }

        $content = (string) file_get_contents($path);

        try {
            $data = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
                'json' => json_decode($content, true, 512, JSON_THROW_ON_ERROR),
                'yaml', 'yml' => Yaml::parse($content),
                default => throw new \InvalidArgumentException(sprintf('Unsupported file extension for "%s", expected json, yaml or yml.', $path)),
            };
        } catch (\JsonException|ParseException $exception) {
            throw new \InvalidArgumentException(sprintf('Unable to parse "%s": %s', $path, $exception->getMessage()), 0, $exception);
        }

        if (! \is_array($data)) {
            throw new \InvalidArgumentException(sprintf('File "%s" must describe a workflow as a map.', $path));
        }

        /** @var array<string, mixed> $data */
        return $data;
    }
}
