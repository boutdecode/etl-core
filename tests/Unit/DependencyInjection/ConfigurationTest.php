<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\DependencyInjection;

use BoutDeCode\ETLCoreBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    #[Test]
    public function loggingLevelDefaultsToInfo(): void
    {
        $config = (new Processor())->processConfiguration(new Configuration(), []);

        self::assertSame('info', $config['logging']['level']);
    }

    #[Test]
    public function loggingLevelAcceptsKnownValue(): void
    {
        $config = (new Processor())->processConfiguration(new Configuration(), [[
            'logging' => [
                'level' => 'debug',
            ],
        ]]);

        self::assertSame('debug', $config['logging']['level']);
    }

    #[Test]
    public function loggingLevelRejectsUnknownValue(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        (new Processor())->processConfiguration(new Configuration(), [[
            'logging' => [
                'level' => 'verbose',
            ],
        ]]);
    }
}
