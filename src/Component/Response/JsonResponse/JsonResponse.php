<?php

namespace App\Component\Response\JsonResponse;

use Symfony\Component\HttpFoundation\JsonResponse as BaseJsonResponse;

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
            $output['warnings'] = $warnings;
        }

        if ($errors) {
            $output['errors'] = $errors;
        }

        parent::__construct($output, $status, $headers);
    }
}
