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
        $context['groups'] = ['read'];
        $context['ignored_attributes'] = ['users'];
        $context['skip_null_values'] = false;
        $data = $this->normalizer->normalize($clan, $format, $context);
        $data['users'] = [];
        $data['admins'] = [];
        foreach ($clan->getUsers() as $userClan) {
            $uuid = $userClan->getUser()->getUuid();
            $data['users'][] = $uuid;
            if ($userClan->getAdmin())
                $data['admins'][] = $uuid;
        }
        return $data;
    }

    public function denormalize($data, $type, $format = null, array $context = [])
    {
        $context['groups'] = ['write'];
        $context['ignored_attributes'] = ['users'];
        $context['allow_extra_attributes'] = false;
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