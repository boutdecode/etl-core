<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class ETLCoreExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);
        $email = $this->extractEmailConfig($config);
        $purge = $this->extractPurgeConfig($config);

        $container->setParameter('boutdecode_etl_core.notifications.email.from', $email['from']);
        $container->setParameter('boutdecode_etl_core.notifications.email.to', $email['to']);
        $container->setParameter('boutdecode_etl_core.notifications.email.subject_prefix', $email['subject_prefix']);

        $container->setParameter('boutdecode_etl_core.purge.enabled', $purge['enabled']);
        $container->setParameter('boutdecode_etl_core.purge.retention_days', $purge['retention_days']);
        $container->setParameter('boutdecode_etl_core.purge.cron_expression', $purge['cron_expression']);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );

        $loader->load('services.yaml');
    }

    public function getAlias(): string
    {
        return 'boutdecode_etl_core';
    }

    /**
     * @param array<array-key, mixed> $config
     *
     * @return array{from: string, to: string[], subject_prefix: string}
     */
    private function extractEmailConfig(array $config): array
    {
        $notifications = $config['notifications'] ?? [];
        $notifications = is_array($notifications) ? $notifications : [];

        $email = $notifications['email'] ?? [];
        $email = is_array($email) ? $email : [];

        $from = $email['from'] ?? null;
        $to = $email['to'] ?? null;
        $subjectPrefix = $email['subject_prefix'] ?? null;

        return [
            'from' => is_string($from) ? $from : 'noreply@example.com',
            'to' => is_array($to) ? array_values(array_filter($to, 'is_string')) : [],
            'subject_prefix' => is_string($subjectPrefix) ? $subjectPrefix : '[ETL]',
        ];
    }

    /**
     * @param array<array-key, mixed> $config
     *
     * @return array{enabled: bool, retention_days: int, cron_expression: string}
     */
    private function extractPurgeConfig(array $config): array
    {
        $purge = $config['purge'] ?? [];
        $purge = is_array($purge) ? $purge : [];

        $enabled = $purge['enabled'] ?? null;
        $retentionDays = $purge['retention_days'] ?? null;
        $cronExpression = $purge['cron_expression'] ?? null;

        return [
            'enabled' => is_bool($enabled) ? $enabled : false,
            'retention_days' => is_int($retentionDays) ? $retentionDays : 30,
            'cron_expression' => is_string($cronExpression) ? $cronExpression : '0 3 * * *',
        ];
    }
}
