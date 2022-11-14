<?php

namespace App\Service;

use App\Entity\ApiUser;
use App\Repository\ApiUserRepository;
use Doctrine\ORM\EntityManagerInterface;

class ApiKeyService
{
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var ApiUserRepository
     */
    private $apiUserRepository;

    public function __construct(EntityManagerInterface $entityManager, ApiUserRepository $apiUserRepository)
    {
        $this->em = $entityManager;
        $this->apiUserRepository = $apiUserRepository;
    }

    public function listApiKeys()
    {
        return $this->apiUserRepository->findAll();
    }

    public function createApiKey(string $name, string $apikey)
    {
        $apiuser = new ApiUser();
        $apiuser->setApiToken($apikey);
        $apiuser->setName($name);

        try {
            $this->em->persist($apiuser);
            $this->em->flush();

            return true;
        } catch (\Exception $exception) {
            return false;
        }
    }

    public function deleteApiKey($name)
    {
        $apiuser = $this->apiUserRepository->findOneBy(['name' => $name]);

        try {
            $this->em->remove($apiuser);
            $this->em->flush();

            return true;
        } catch (\Exception $exception) {
            return false;
        }
    }
}
