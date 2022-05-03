<?php

namespace App\Component\Normalizer;

use App\Component\Exception\LoggedException;

abstract class AbstractNormalizer implements NormalizerInterface
{
    /**
     * @param string|string[] $key
     */
    protected function extractIncludes(array $includes, string|array $key = ''): array
    {
        if (!$key) {
            return $includes;
        }

        $extracted = [];

        if (is_string($key)) {
            $key = [$key];
        }

        foreach ($includes as $item) {
            foreach ($key as $keyItem) {
                if (0 === strpos($item, $keyItem . '.')) {
                    $extracted[] = substr($item, strlen($keyItem) + 1);
                }
            }
        }

        return $extracted;
    }

    protected function createException(string $message, array $context = []): LoggedException
    {
        return (new LoggedException($message))
            ->setExceptionedClass(static::class)
            ->setLoggedContext($context);
    }
}
