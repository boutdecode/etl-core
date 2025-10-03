<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\CQS\Application\Exception;

class CommandHandlerException extends \Exception
{
    public function __construct(
        string $message,
        \Exception $exception
    ) {
        parent::__construct(
            sprintf(
                '%s in %s: %s',
                $message,
                $exception->getFile(),
                $exception->getMessage()
            ),
            (int) $exception->getCode(),
            $exception
        );
    }

    public function getFirst(): ?\Throwable
    {
        $previous = $this;
        while ($exception = $previous->getPrevious()) {
            $previous = $exception;
        }

        return $previous;
    }
}
