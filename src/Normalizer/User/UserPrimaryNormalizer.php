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
            'avatar_url' => !$data->hasAvatar() ? '' : $this->parameters->get('app.base_url') . $data->getAvatarPublicPathname(),
            'title' =>      $data->getTitle()
        ];

        return $output;
    }
}
