<?php

namespace App\Component\Exception;

use Psr\Log\LoggerInterface;

class LoggedException extends \Exception
{
    private LoggerInterface $logger;
    private ?string $exceptionedClass = null;
    private array $loggedContext = [];

    public function getLogger(): ?LoggerInterface
    {
        return $this->logger ?? null;
    }

    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;
        return $this;
    }

    public function getExceptionedClass(): ?string
    {
        return $this->exceptionedClass;
    }

    public function setExceptionedClass(?string $exceptionedClass): self
    {
        $this->exceptionedClass = $exceptionedClass;
        return $this;
    }

    public function getLoggedContext(): array
    {
        return $this->loggedContext;
    }

    public function setLoggedContext(array $context): self
    {
        $this->loggedContext = $context;
        return $this;
    }
}
