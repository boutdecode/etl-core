<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Notifications\Domain\Model;

interface NotificationProvider
{
    public function getCode(): string;

    public function notify(NotificationMessage $message): void;
}
