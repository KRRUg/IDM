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
     * Set to true to serialize just the UUID
     */
    public const DEPTH = 'depth';

    private ObjectNormalizer $on;

    /**
     * @param ObjectNormalizer $normalizer
     */
    public function __construct(ObjectNormalizer $normalizer)
    {
        $this->on = $normalizer;
    }

    public function normalize($object, $format = null, array $context = [])
    {
        $depth = array_key_exists(self::DEPTH, $context) && is_int($context[self::DEPTH]) ? intval($context[self::DEPTH]) : 1;
        $depth = $depth < 0 ? 0 : $depth;

        $context[ObjectNormalizer::GROUPS] = ['read'];

        switch (true) {
            case $object instanceof User:
                return $this->normalizeUser($object, $depth, $format, $context);
            case $object instanceof Clan:
                return $this->normalizeClan($object, $depth, $format, $context);
            default:
                throw new InvalidArgumentException('Class not supported');
        }
    }

    private function normalizeClan($object, int $depth, $format = null, array $context = [])
    {
        if ($depth == 0) {
            $context[ObjectNormalizer::ATTRIBUTES] = ['uuid'];
            return $this->on->normalize($object, $format, $context);
        }

        $context[ObjectNormalizer::IGNORED_ATTRIBUTES] = ['users'];
        $data = $this->on->normalize($object, $format, $context);
        $data['users'] = [];
        $data['admins'] = [];
        foreach ($object->getUsers() as $userClan) {
            $user = $this->normalizeUser($userClan->getUser(), $depth - 1, $format, $context);
            if ($userClan->getAdmin())
                $data['admins'][] = $user;
            else
                $data['users'][] = $user;
        }
        return $data;
    }

    private function normalizeUser($object, int $depth, $format = null, array $context = [])
    {
        if ($depth == 0) {
            $context[ObjectNormalizer::ATTRIBUTES] = ['uuid'];
            return $this->on->normalize($object, $format, $context);
        }

        $context[ObjectNormalizer::IGNORED_ATTRIBUTES] = ['clans'];
        $data = $this->on->normalize($object, $format, $context);
        $data['clans'] = [];

        foreach ($object->getClans() as $userClan) {
            $data['clans'][] = $this->normalizeClan($userClan->getClan(), $depth - 1, $format, $context);
        }
        return $data;
    }

    public function denormalize($data, $type, $format = null, array $context = [])
    {
        $context[ObjectNormalizer::GROUPS] = ['write'];
        $context[ObjectNormalizer::IGNORED_ATTRIBUTES][] = 'clans';
        $context[ObjectNormalizer::IGNORED_ATTRIBUTES][] = 'users';
        $context[ObjectNormalizer::IGNORED_ATTRIBUTES][] = 'admins';
        if (!array_key_exists(ObjectNormalizer::ALLOW_EXTRA_ATTRIBUTES, $context)) {
            $context[ObjectNormalizer::ALLOW_EXTRA_ATTRIBUTES] = true;
        }
        return $this->on->denormalize($data, $type, $format, $context);
    }

    public function supportsNormalization($data, $format = null, array $context = [])
    {
        return $data instanceof User
            || $data instanceof Clan;
    }

    public function supportsDenormalization($data, $type, $format = null, array $context = [])
    {
        return is_a($type, User::class, true)
            || is_a($type, Clan::class, true);
    }
}