<?php

namespace App\Controller;

use App\Component\Doctrine\EntityInterface;
use App\Component\Doctrine\PaginatedCollection;
use App\Component\Normalizer\NormalizerFactory;
use App\Component\Normalizer\NormalizerInterface;
use App\Entity\User\User;
use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as BaseController;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractController extends BaseController
{
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            'normalizer.factory' => NormalizerFactory::class,
            'translator' =>         TranslatorInterface::class
        ]);
    }

    protected function getUser(): ?User
    {
        return parent::getUser();
    }

    /**
     * Checks if a user from the Security Token Storage is logged.
     */
    protected function isLogged(): bool
    {
        return $this->getUser() instanceof User;
    }

    /**
     * Checks if the any of given attributes is granted against the current authentication token and optionally supplied subject.
     *
     * @param string[] $attributes
     */
    protected function isGrantedAny(array $attributes, $subject = null): bool
    {
        foreach ($attributes as $attribute) {
            if ($this->isGranted($attribute, $subject)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gets the current request instance.
     */
    protected function getRequest(): Request
    {
        /** @var RequestStack */
        $requestStack = $this->container->get('request_stack');

        return $requestStack->getCurrentRequest();
    }

    /**
     * Gets the current request body content.
     */
    protected function getRequestContent(): string
    {
        return $this->getRequest()->getContent();
    }

    /**
     * Gets query parameters bag of current request instance.
     */
    protected function getRequestQuery(): InputBag
    {
        return $this->getRequest()->query;
    }

    /**
     * Gets all query parameters of current request instance.
     */
    protected function getQueryParameters(): array
    {
        return $this->getRequest()->query->all();
    }

    /**
     * Gets the query parameter of current request instance.
     */
    protected function getQueryParameter(string $key, $default = null): mixed
    {
        return $this->getRequest()->query->get($key, $default);
    }

    /**
     * Gets decoded JSON content of the current request.
     */
    protected function decodeRequest(): array
    {
        return json_decode($this->getRequest()->getContent(), true) ?? [];
    }

    /**
     * Checks if the request method is of specified type.
     */
    protected function isRequestMethod(string $method): bool
    {
        return $this->getRequest()->isMethod($method);
    }

    /**
     * Checks if the request method is POST.
     */
    protected function isRequestMethodPost(): bool
    {
        return $this->isRequestMethod('POST');
    }

    /**
     * Checks if the request method is GET.
     */
    protected function isRequestMethodGet(): bool
    {
        return $this->isRequestMethod('GET');
    }

    /**
     * Gets Doctrine Entity (Object) Manager.
     */
    protected function getDoctrineManager(): ObjectManager
    {
        return $this->container->get('doctrine')->getManager();
    }

    /**
     * Gets the normalizers container.
     */
    protected function getNormalizersFactory(): NormalizerFactory
    {
        return $this->container->get('normalizer.factory');
    }

    /**
     * Gets the normalizer.
     */
    protected function getNormalizer(string $normalizerClass): NormalizerInterface
    {
        return $this->getNormalizersFactory()->get($normalizerClass);
    }

    /**
     * Normalizes given entity or array with entities.
     *
     * @param EntityInterface|PaginatedCollection|Collection|EntityInterface[] $data
     */
    protected function normalize(string $normalizerClass, EntityInterface|PaginatedCollection|Collection|array $data, array $includes = []): ?array
    {
        return $this->getNormalizersFactory()->normalize($normalizerClass, $data, $includes);
    }

    /**
     * Translates the given message.
     */
    protected function trans(string $template, array $parameters = []): string
    {
        return $this->container->get('translator')->trans($template, $parameters);
    }
}
