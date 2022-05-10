<?php

namespace App\Controller;

use App\Component\Config\ConfigManager;
use App\Component\Converter\ArrayConverter;
use App\Component\Response\JsonResponse\SuccessResponse;
use Symfony\Component\Routing\Annotation\Route;

class ConfigController extends AbstractController
{
    public function __construct(
        private ArrayConverter  $arrayConverter,
        private ConfigManager   $configManager
    )
    {}

    #[Route(name: 'config', path: '/config', methods: ['GET'])]
    public function config(): SuccessResponse
    {
        $parameters = $this->configManager->parameters();

        if ((int) $this->getRequestQuery()->get('flatten')) {
            $parameters = $this->arrayConverter->flatten($parameters);
        }

        return new SuccessResponse(data: ['parameters' => $parameters]);
    }
}