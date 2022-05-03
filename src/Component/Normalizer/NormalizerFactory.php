<?php

namespace App\Component\Normalizer;

use App\Component\Doctrine\EntityInterface;
use App\Component\Doctrine\PaginatedCollection;
use App\Component\Exception\LoggedException;
use App\Component\Normalizer\NormalizerInterface;
use Doctrine\Common\Collections\Collection;
use Psr\Container\ContainerInterface;

class NormalizerFactory
{
    public function __construct(
        protected ContainerInterface $container
    )
    {}

    /**
     * Gets the normalizer.
     */
    public function get(string $normalizerClass): NormalizerInterface
    {
        return $this->container->get($normalizerClass);
    }

    /**
     * Normalizes the given entity by defined normalizer.
     *
     * @param EntityInterface|PaginatedCollection|Collection|EntityInterface[]|null $data
     */
    public function normalize(string $normalizerClass, EntityInterface|PaginatedCollection|Collection|array|null $data, array $includes = []): ?array
    {
        if (null === $data) {
            return null;
        }

        if (!$this->get($normalizerClass)->supports($data)) {
            throw $this->createException(sprintf('"%s" does not supports given "%s" resource', $normalizerClass, is_object($data) ? get_class($data) : gettype($data)));
        }

        foreach ($includes as $include) {
            if (false !== strpos($include, '.')) {
                $include = substr($include, 0, strpos($include, '.'));

                if (!in_array($include, $includes)) {
                    $includes[] = $include;
                }
            }
        }

        return $this->get($normalizerClass)->normalize($data, $includes);
    }

    private function createException(string $message, array $context = []): LoggedException
    {
        return (new LoggedException($message))
            ->setExceptionedClass(self::class)
            ->setLoggedContext($context);
    }
}
