<?php


namespace App\Serializer;


use App\Entity\Clan;
use Symfony\Component\Serializer\Exception\CircularReferenceException;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class ClanNormalizer implements ContextAwareNormalizerInterface
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

    /**
     * @param Clan $clan
     * @param null $format
     * @param array $context
     * @return array
     * @throws ExceptionInterface
     */
    public function normalize($clan, $format = null, array $context = [])
    {
        $context['attributes'] = ['uuid', 'name', 'clantag', 'website', 'description', 'createdAt', 'modifiedAt'];
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

    public function supportsNormalization($data, $format = null, array $context = [])
    {
        return $data instanceof Clan;
    }
}