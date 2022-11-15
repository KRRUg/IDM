<?php

namespace App\Serializer;

use App\Entity\Clan;
use App\Entity\User;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class UserClanNormalizer implements ContextAwareNormalizerInterface, ContextAwareDenormalizerInterface
{
    /**
     * Set to true to serialize just the UUID.
     */
    final public const DEPTH = 'depth';

    public function __construct(private readonly ObjectNormalizer $on)
    {
    }

    public function normalize($object, $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $depth = array_key_exists(self::DEPTH, $context) && is_int($context[self::DEPTH]) ? intval($context[self::DEPTH]) : 1;
        $depth = $depth < 0 ? 0 : $depth;
        $depth = $depth > 3 ? 3 : $depth;

        $context[ObjectNormalizer::GROUPS] = ['read'];
        $context[ObjectNormalizer::IGNORED_ATTRIBUTES] = ['users', 'clans'];

        return match (true) {
            $object instanceof User => $this->normalizeUser($object, $depth, $format, $context),
            $object instanceof Clan => $this->normalizeClan($object, $depth, $format, $context),
            default => throw new InvalidArgumentException('Class not supported'),
        };
    }

    private function normalizeClan($object, int $depth, $format = null, array $context = [])
    {
        if ($depth == 0) {
            return ['uuid' => $object->getUuid()->toString()];
        }

        $data = $this->on->normalize($object, $format, $context);
        $data['users'] = [];
        $data['admins'] = [];
        foreach ($object->getUsers() as $userClan) {
            $user = $this->normalizeUser($userClan->getUser(), $depth - 1, $format, $context);
            $data['users'][] = $user;
            if ($userClan->getAdmin()) {
                $data['admins'][] = $user;
            }
        }

        return $data;
    }

    private function normalizeUser($object, int $depth, $format = null, array $context = [])
    {
        if ($depth == 0) {
            return ['uuid' => $object->getUuid()->toString()];
        }

        $data = $this->on->normalize($object, $format, $context);
        $data['clans'] = [];

        foreach ($object->getClans() as $userClan) {
            $data['clans'][] = $this->normalizeClan($userClan->getClan(), $depth - 1, $format, $context);
        }

        return $data;
    }

    public function denormalize($data, $type, $format = null, array $context = []): mixed
    {
        $context[ObjectNormalizer::GROUPS] = ['write'];
        $context[ObjectNormalizer::IGNORED_ATTRIBUTES] = ['users', 'admins', 'clans'];
        if (!array_key_exists(ObjectNormalizer::ALLOW_EXTRA_ATTRIBUTES, $context)) {
            $context[ObjectNormalizer::ALLOW_EXTRA_ATTRIBUTES] = true;
        }

        return $this->on->denormalize($data, $type, $format, $context);
    }

    public function supportsNormalization($data, $format = null, array $context = []): bool
    {
        return $data instanceof User
            || $data instanceof Clan;
    }

    public function supportsDenormalization($data, $type, $format = null, array $context = []): bool
    {
        return is_a($type, User::class, true)
            || is_a($type, Clan::class, true);
    }
}
