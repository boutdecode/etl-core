<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\ETL\Infrastructure\Step\Transformer;

use BoutDeCode\ETLCoreBundle\ETL\Domain\Model\AbstractTransformerStep;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

final class FilterTransformStep extends AbstractTransformerStep
{
    public const string CODE = 'etl.transformer.filter';

    protected string $code = self::CODE;

    public function __construct(
        private readonly string $filterExpression = 'item'
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfigurationDescription(): array
    {
        return [
            'filterExpression' => 'A Symfony Expression Language expression used to filter items. The expression can reference the current item using the variable item.',
        ];
    }

    /**
     * @return array<mixed>
     */
    public function transform(mixed $data, array $configuration = []): array
    {
        if (! is_array($data)) {
            throw new \InvalidArgumentException('Data must be an array');
        }

        $filterExpression = $configuration['filterExpression'] ?? $this->configuration['filterExpression'] ?? $this->filterExpression;
        $filterExpressionStr = is_string($filterExpression) ? $filterExpression : $this->filterExpression;
        $expressionLanguage = new ExpressionLanguage();

        return array_values(array_filter($data, fn (mixed $item): bool => (bool) $expressionLanguage->evaluate($filterExpressionStr, [
            'item' => (object) $item,
        ])));
    }
}
