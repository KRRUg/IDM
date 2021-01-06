<?php


namespace App\Serializer;


use App\Entity\User;
use Symfony\Component\Serializer\Exception\BadMethodCallException;
use Symfony\Component\Serializer\Exception\CircularReferenceException;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Exception\ExtraAttributesException;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Exception\RuntimeException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class UserNormalizer implements ContextAwareNormalizerInterface, ContextAwareDenormalizerInterface
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
        $context['groups'] = ['read'];
        $context['ignored_attributes'] = ['clans'];
        $context['skip_null_values'] = false;
        $data = $this->normalizer->normalize($user, $format, $context);
        $data['clans'] = [];
        foreach ($user->getClans() as $userClan) {
            $uuid = $userClan->getClan()->getUuid();
            $data['clans'][] = $uuid;
        }
        return $data;
    }

    public function denormalize($data, $type, $format = null, array $context = [])
    {
        $context['groups'] = ['write'];
        $context['ignored_attributes'] = ['clans'];
        $context['allow_extra_attributes'] = false;
        return $this->normalizer->denormalize($data, $type, $format, $context);
    }

    public function supportsNormalization($data, $format = null, array $context = [])
    {
        return $data instanceof User;
    }

    public function supportsDenormalization($data, $type, $format = null, array $context = [])
    {
        return is_a($type, User::class, true);
    }
}