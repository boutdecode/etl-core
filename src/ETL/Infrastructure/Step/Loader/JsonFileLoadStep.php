<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\ETL\Infrastructure\Step\Loader;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\ETL\Domain\Attribute\AsExecutableStep;
use BoutDeCode\ETLCoreBundle\ETL\Domain\Model\AbstractLoaderStep;
use function Flow\ETL\Adapter\JSON\to_json;
use function Flow\ETL\DSL\data_frame;
use function Flow\ETL\DSL\from_array;
use function Flow\ETL\DSL\overwrite;

#[AsExecutableStep(
    code: 'etl.loader.json_file',
    configurationDescription: [
        'destination' => 'Path to the JSON file to load data into',
        'options' => 'JSON encoding options (default: 0)',
    ],
)]
final class JsonFileLoadStep extends AbstractLoaderStep
{
    public function __construct(
        private readonly int $options = 0,
    ) {
    }

    public function load(mixed $data, mixed $destination, Context $context, array $configuration = []): bool
    {
        if (! is_string($destination)) {
            throw new \InvalidArgumentException('File path must be a string');
        }

        $options = $configuration['options'] ?? $this->configuration['options'] ?? $this->options;
        $optionsInt = is_int($options) ? $options : $this->options;

        // Ensure $data is iterable<array<mixed>> before passing it to from_array()
        /** @var iterable<array<mixed>> $iterableData */
        $iterableData = is_iterable($data) ? $data : [$data];

        data_frame()
            ->read(from_array($iterableData))
            ->collect()
            ->mode(overwrite())
            ->write(to_json($destination, $optionsInt))
            ->run();

        return true;
    }
}
