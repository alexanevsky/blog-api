<?php

namespace App\Normalizer\User;

class UserPrimaryNormalizer extends UserNormalizer
{
    public function normalize($data, array $includes = []): array
    {
        $output = [
            'id' =>         $data->getId(),
            'username' =>   $data->getUsername(),
            'alias' =>      $data->getAlias(),
            'avatar' =>     $data->getAvatar(),
            'title' =>      $data->getTitle()
        ];

        return $output;
    }
}
