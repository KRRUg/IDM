<?php


namespace App\Serializer;


use App\Entity\User;
use Symfony\Component\Serializer\Exception\CircularReferenceException;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class UserNormalizer implements ContextAwareNormalizerInterface
{
    private ObjectNormalizer $normalizer;

    /**
     * ClanNormalizer constructor.
     * @param ObjectNormalizer $normalizer
     */
    public function __construct(ObjectNormalizer $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    public function normalize($user, $format = null, array $context = [])
    {
        $context['attributes'] = ['uuid', 'email', 'nickname', 'firstname', 'surname', 'postcode', 'city', 'street', 'country', 'phone', 'gender', 'emailConfirmed', 'isSuperadmin', 'website', 'steamAccount', 'registeredAt', 'modifiedAt', 'hardware', 'infoMails', 'statements', 'birthdate'];
        $context['skip_null_values'] = false;
        $data = $this->normalizer->normalize($user, $format, $context);
        $data['clans'] = [];
        foreach ($user->getClans() as $userClan) {
            $uuid = $userClan->getClan()->getUuid();
            $data['clans'][] = $uuid;
        }
        return $data;
    }

    public function supportsNormalization($data, $format = null, array $context = [])
    {
        return $data instanceof User;
    }
}