<?php


namespace App\Serializer;


use App\Entity\Clan;
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

class ClanNormalizer implements ContextAwareNormalizerInterface, ContextAwareDenormalizerInterface
{
    /**
     * Set to true to serialze just the UUID
     */
    public const UUID_ONLY = 'uuid_only';

    private ObjectNormalizer $normalizer;

    /**
     * ClanNormalizer constructor.
     * @param ObjectNormalizer $normalizer
     */
    public function __construct(ObjectNormalizer $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    public function normalize($clan, $format = null, array $context = [])
    {
        if (array_key_exists(self::UUID_ONLY, $context) && is_bool($context[self::UUID_ONLY]) && $context[self::UUID_ONLY]) {
            $context[ObjectNormalizer::ATTRIBUTES] = ['uuid'];
            $data = $this->normalizer->normalize($clan, $format, $context);
        } else {
            $context[ObjectNormalizer::GROUPS] = ['read'];
            $context[ObjectNormalizer::IGNORED_ATTRIBUTES] = ['users'];
            if (!array_key_exists(ObjectNormalizer::SKIP_NULL_VALUES, $context)) {
                $context[ObjectNormalizer::SKIP_NULL_VALUES] = false;
            }
            $data = $this->normalizer->normalize($clan, $format, $context);
            $data['users'] = [];
            $data['admins'] = [];
            $context[ObjectNormalizer::ATTRIBUTES] = ['uuid'];
            foreach ($clan->getUsers() as $userClan) {
                $user = $this->normalizer->normalize($userClan->getUser(), $format, $context);
                $data['users'][] = $user;
                if ($userClan->getAdmin())
                    $data['admins'][] = $user;
            }
        }
        return $data;
    }

    public function denormalize($data, $type, $format = null, array $context = [])
    {
        $context[ObjectNormalizer::GROUPS] = ['write'];
        $context[ObjectNormalizer::IGNORED_ATTRIBUTES][] = 'users';
        if (!array_key_exists(ObjectNormalizer::ALLOW_EXTRA_ATTRIBUTES, $context)) {
            $context[ObjectNormalizer::ALLOW_EXTRA_ATTRIBUTES] = true;
        }
        return $this->normalizer->denormalize($data, $type, $format, $context);
    }

    public function supportsNormalization($data, $format = null, array $context = [])
    {
        return $data instanceof Clan;
    }

    public function supportsDenormalization($data, $type, $format = null, array $context = [])
    {
        return is_a($type, Clan::class, true);
    }
}