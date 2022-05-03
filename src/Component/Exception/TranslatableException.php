<?php

namespace App\Component\Exception;

class TranslatableException extends LoggedException
{
    protected string $messageKey = 'common.messages.failed';
    protected array $messageParameters = [];

    public function setMessage(string $key, ?array $parameters = []): self
    {
        $this->messageKey = $key;
        $this->messageParameters = $parameters ?? [];
        return $this;
    }

    public function getMessageKey(): string
    {
        return $this->messageKey ?: $this->getMessage();
    }

    public function getMessageParameters(): array
    {
        return $this->messageParameters;
    }
}
