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

        $container->setParameter('boutdecode_etl_core.notifications.email.from', $email['from']);
        $container->setParameter('boutdecode_etl_core.notifications.email.to', $email['to']);
        $container->setParameter('boutdecode_etl_core.notifications.email.subject_prefix', $email['subject_prefix']);

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
}
