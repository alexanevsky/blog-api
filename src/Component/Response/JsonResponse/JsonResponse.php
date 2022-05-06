<?php

namespace App\Component\Response\JsonResponse;

use Symfony\Component\HttpFoundation\JsonResponse as BaseJsonResponse;
use Symfony\Component\Translation\TranslatableMessage;

class JsonResponse extends BaseJsonResponse
{
    public const STATUS_CODE = 200; // OK

    public function __construct(
        bool    $success =              true,
        ?string $message =              '',
        ?array  $messageParameters =    [],
        ?array  $data =                 [],
        ?array  $errors =               [],
        ?array  $warnings =             [],
        int     $status =               self::STATUS_CODE,
        array   $headers =              []
    )
    {
        $output = [
            'success' => $success
        ];

        if ($message) {
            $output['message'] = $message;
            $output['message_parameters'] = $messageParameters;
        }

        if ($data) {
            $output['response'] = $data;
        }

        if ($warnings) {
            $output['warnings'] = array_map(function ($warning) {
                return !$warning instanceof TranslatableMessage ? $warning : [
                    'message' => $warning->getMessage(),
                    'parameters' => $warning->getParameters()
                ];
            }, $warnings);
        }

        if ($errors) {
            $output['errors'] = array_map(function ($error) {
                return !$error instanceof TranslatableMessage ? $error : [
                    'message' => $error->getMessage(),
                    'parameters' => $error->getParameters()
                ];
            }, $errors);
        }

        parent::__construct($output, $status, $headers);
    }
}
