<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\Notifications\Domain\Resolver;

use BoutDeCode\ETLCoreBundle\Notifications\Domain\Model\NotificationProvider;
use BoutDeCode\ETLCoreBundle\Notifications\Domain\Resolver\DefaultNotificationProviderResolver;
use BoutDeCode\ETLCoreBundle\Notifications\Domain\Resolver\NotificationProviderResolver;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DefaultNotificationProviderResolverTest extends TestCase
{
    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function constructWithoutProvidersShouldCreateEmptyResolver(): void
    {
        $resolver = new DefaultNotificationProviderResolver();

        $this->assertNull($resolver->resolve('email'));
        $this->assertSame([], $resolver->list());
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function constructWithProvidersShouldAddAllProviders(): void
    {
        $email = $this->createMock(NotificationProvider::class);
        $email->method('getCode')->willReturn('email');

        $slack = $this->createMock(NotificationProvider::class);
        $slack->method('getCode')->willReturn('slack');

        $resolver = new DefaultNotificationProviderResolver([$email, $slack]);

        $this->assertSame($email, $resolver->resolve('email'));
        $this->assertSame($slack, $resolver->resolve('slack'));
        $this->assertSame([$email, $slack], $resolver->list());
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function addProviderShouldAddProviderToResolver(): void
    {
        $resolver = new DefaultNotificationProviderResolver();

        $provider = $this->createMock(NotificationProvider::class);
        $provider->method('getCode')->willReturn('email');

        $resolver->addProvider($provider);

        $this->assertSame($provider, $resolver->resolve('email'));
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function resolveShouldReturnNullForNonExistentProvider(): void
    {
        $provider = $this->createMock(NotificationProvider::class);
        $provider->method('getCode')->willReturn('email');

        $resolver = new DefaultNotificationProviderResolver([$provider]);

        $this->assertNull($resolver->resolve('non.existent'));
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function resolverShouldImplementInterface(): void
    {
        $resolver = new DefaultNotificationProviderResolver();

        $this->assertInstanceOf(NotificationProviderResolver::class, $resolver);
    }
}
