<?php

namespace App\Normalizer\User;

use App\Component\Converter\ContactConverter;
use App\Component\Normalizer\AbstractNormalizer;
use App\Component\Normalizer\NormalizerFactory;
use App\Entity\User\User;
use App\Security\Voter\SecurityVoter;
use App\Security\Voter\User\UserVoter;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Security\Core\Security;

class UserNormalizer extends AbstractNormalizer
{
    public function __construct(
        private ContactConverter        $contactConverter,
        private ContainerBagInterface   $parameters,
        private NormalizerFactory       $normalizer,
        private Security                $security
    )
    {}

    /**
     * {@inheritDoc}
     */
    public function supports($data): bool
    {
        return $data instanceof User;
    }

    /**
     * {@inheritDoc}
     *
     * @param User $data
     */
    public function normalize($data, array $includes = []): array
    {
        $output = [
            'id' =>                             $data->getId(),
            'username' =>                       $data->getUsername(),
            'alias' =>                          $data->getAlias(),
            'email' =>                          $data->isEmailHidden() && !$this->security->isGranted(UserVoter::ATTR_UPDATE, $data) ? null : $data->getEmail(),
            'is_email_hidden' =>                $data->isEmailHidden(),
            'phone' =>                          $data->getPhone(),
            'website' =>                        $data->getWebsite(),
            'contacts' =>                       array_values($this->contactConverter->addLinks($this->contactConverter->filter($data->getContacts(), $this->parameters->get('app.users.contacts')))),
            'birthdate' =>                      $data->getBirthdate(),
            'avatar' =>                         $data->getAvatar(),
            'title' =>                          $data->getTitle(),
            'city' =>                           $data->getCity(),
            'biography' =>                      $data->getBiography(),
            'first_useragent' =>                $data->getFirstUseragent(),
            'first_ip' =>                       $data->getFirstIp(),
            'is_banned' =>                      $data->isBanned(),
            'is_communication_banned' =>        $data->isCommunicationBanned(),
            'is_trashed' =>                     $data->isTrashed(),
            'is_erased' =>                      $data->isErased(),
            'is_allowed_adv_notifications' =>   $data->isAllowedAdvNotifications(),
            'roles' =>                          $data->getRoles(),
            'sorting' =>                        $data->getSorting(),
            'created_at' =>                     $data->getCreatedAt()?->format('c'),
            'updated_at' =>                     $data->getUpdatedAt()?->format('c'),
            'trashed_at' =>                     $data->getTrashedAt()?->format('c'),
            'erased_at' =>                      $data->getErasedAt()?->format('c')
        ];

        if (in_array('created_by', $includes)) {
            $output['created_by'] = $this->normalizer->normalize(UserNormalizer::class, $data->getCreatedBy(), $this->extractIncludes($includes, 'created_by'));
        }

        if (in_array('permissions', $includes)) {
            $output['permissions'] = array_combine(UserVoter::ATTRIBUTES, array_map(function (string $attribute) use ($data) {
                return $this->security->isGranted($attribute, $data);
            }, UserVoter::ATTRIBUTES));
        }

        if (in_array('security_permissions', $includes, true)) {
            $output['security_permissions'] = array_combine(SecurityVoter::ATTRIBUTES, array_map(function (string $attribute) {
                return $this->security->isGranted($attribute);
            }, SecurityVoter::ATTRIBUTES));
        }

        return $output;
    }
}
