<?php

namespace App\Controller;

use App\Component\Converter\ArrayConverter;
use App\Component\Response\JsonResponse\AccessDeniedResponse;
use App\Component\Response\JsonResponse\NotFoundResponse;
use App\Component\Response\JsonResponse\SuccessResponse;
use App\Component\Translation\Generator as TranslationGenerator;
use App\Entity\User\User;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Yaml\Yaml;

class TranslationController extends AbstractController
{
    public function __construct(
        private ArrayConverter          $arrayConverter,
        private ContainerBagInterface   $parameters,
        private TranslationGenerator    $generator
    )
    {}

    #[Route(name: 'translations_lang', path: '/translations/{lang<[\w]{2}>}', methods: ['GET'])]
    public function lang(string $lang): JsonResponse
    {
        $file = sprintf('%s/%s.%s.yaml', $this->parameters->get('translator.default_path'), 'messages+intl-icu', $lang);

        try {
            $translations = Yaml::parseFile($file);
        } catch (\Exception) {
            return new NotFoundResponse('translations.messages.lang_not_found', ['lang' => $lang]);
        }

        if ((int) $this->getRequestQuery()->get('flatten')) {
            $translations = $this->arrayConverter->flatten($translations);
        }

        return new SuccessResponse(data: [
            'translations' => $translations
        ]);
    }

    #[Route(name: 'translations_generate', path: '/translations/generate', methods: ['POST'])]
    public function generate(): JsonResponse
    {
        if (!$this->isGranted(User::ROLE_ADMIN)) {
            return new AccessDeniedResponse(needAuth: !$this->isLogged());
        }

        $this->generator->generate();

        return new SuccessResponse('translations.messages.generate.generated');
    }
}
