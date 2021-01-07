<?php

namespace App\Controller\Rest;

use App\Entity\Clan;
use App\Entity\User;
use App\Entity\UserClan;
use App\Repository\ClanRepository;
use App\Repository\UserClanRepository;
use App\Repository\UserRepository;
use App\Transfer\Error;
use App\Transfer\PaginationCollection;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcher;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Ramsey\Uuid\Uuid;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Class ClanController.
 *
 * @Rest\Route("/clans")
 */
class ClanController extends AbstractFOSRestController
{
    private EntityManagerInterface $em;
    private ClanRepository $clanRepository;
    private UserRepository $userRepository;
    private UserClanRepository $userClanRepository;
    private PasswordEncoderInterface $passwordEncoder;

    public function __construct(EntityManagerInterface $entityManager, ClanRepository $clanRepository, UserRepository $userRepository, UserClanRepository $userClanRepository, PasswordEncoderInterface $passwordEncoder)
    {
        $this->em = $entityManager;
        $this->clanRepository = $clanRepository;
        $this->userRepository = $userRepository;
        $this->userClanRepository = $userClanRepository;
        $this->passwordEncoder = $passwordEncoder;
    }

    /**
     * Returns a single Clanobject.
     *
     * @Rest\Get("/{uuid}", requirements= {"uuid"="[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}"})
     * @ParamConverter()
     */
    public function getClanAction(Clan $clan)
    {
        $view = $this->view($clan);
        return $this->handleView($view);
    }

    /**
     * Creates a Clan.
     *
     * @Rest\Post("")
     * @ParamConverter("new", converter="fos_rest.request_body",
     *     options={
     *      "deserializationContext": {"allow_extra_attributes": false},
     *      "validator": {"groups": {"Transfer", "Create"} }
     *     })
     */
    public function createClanAction(Clan $new, ConstraintViolationListInterface $validationErrors)
    {
        if (count($validationErrors) > 0) {
            $view = $this->view(Error::withMessageAndDetail("Invalid JSON Body supplied, please check the Documentation", $validationErrors[0]), Response::HTTP_BAD_REQUEST);
            return $this->handleView($view);
        }

        $new->setJoinPassword($this->passwordEncoder->encodePassword($new->getJoinPassword(), null));

        $this->em->persist($new);
        $this->em->flush();

        $view = $this->view($new, Response::HTTP_CREATED);
        return $this->handleView($view);
    }

    /**
     * Edits a clan.
     *
     * @Rest\Patch("/{uuid}", requirements= {"uuid"="[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}"})
     * @ParamConverter("clan", class="App\Entity\Clan")
     * @ParamConverter("update", converter="fos_rest.request_body",
     *     options={
     *      "deserializationContext": {"allow_extra_attributes": false},
     *      "validator": {"groups": {"Transfer"} },
     *      "attribute_to_populate": "clan",
     *     })
     */
    public function editClanAction(Clan $update, ConstraintViolationListInterface $validationErrors)
    {
        if (count($validationErrors) > 0) {
            $view = $this->view(Error::withMessageAndDetail("Invalid JSON Body supplied, please check the Documentation", $validationErrors[0]), Response::HTTP_BAD_REQUEST);
            return $this->handleView($view);
        }

        if ($this->passwordEncoder->needsRehash($update->getJoinPassword())) {
            $update->setJoinPassword($this->passwordEncoder->encodePassword($update->getJoinPassword(), null));
        }

        $this->em->persist($update);
        $this->em->flush();

        return $this->handleView($this->view($update));
    }

    /**
     * Delete a Clan.
     *
     * @Rest\Delete("/{uuid}", requirements= {"uuid"="[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}"})
     * @ParamConverter()
     */
    public function removeClanAction(Clan $clan)
    {
        $this->em->remove($clan);
        $this->em->flush();

        $view = $this->view(null, Response::HTTP_NO_CONTENT);
        return $this->handleView($view);
    }

    /**
     * Returns all Clan objects with filter.
     *
     * @Rest\Get("")
     * @Rest\QueryParam(name="page", requirements="\d+", default="1")
     * @Rest\QueryParam(name="limit", requirements="\d+", default="10")
     * @Rest\QueryParam(name="q", default="")
     */
    public function getClansAction(ParamFetcher $fetcher)
    {
        $page = intval($fetcher->get('page'));
        $limit = intval($fetcher->get('limit'));
        $filter = $fetcher->get('q');

        $qb = $this->clanRepository->findAllWithActiveUsersQueryBuilder($filter);

        //set useOutputWalker to false otherwise we cannot Paginate Entities with INNER/LEFT Joins
        $pager = new Pagerfanta(new QueryAdapter($qb, true, false));
        $pager->setMaxPerPage($limit);
        $pager->setCurrentPage($page);

        $clans = array();
        foreach ($pager->getCurrentPageResults() as $clan) {
            $clans[] = $clan;
        }

        $collection = new PaginationCollection(
            $clans,
            $pager->getNbResults()
        );

        $view = $this->view($collection);
        return $this->handleView($view);
    }

    /**
     * Adds a User to a Clan.
     *
     * @Rest\Post("/{uuid}/users", requirements= {"uuid"="[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}"})
     * @ParamConverter("clan", options={"mapping": {"uuid": "uuid"}})
     * @ParamConverter("user_id", converter="fos_rest.request_body", class="Ramsey\Uuid\Uuid")
     */
    public function addMemberAction(Clan $clan, Uuid $user_id, ConstraintViolationListInterface $validationErrors)
    {
        if (count($validationErrors) > 0) {
            $view = $this->view(Error::withMessageAndDetail('Invalid JSON Body supplied, please check the Documentation', $validationErrors[0]), Response::HTTP_BAD_REQUEST);
            return $this->handleView($view);
        }

        $user = $this->userRepository->findBy(['uuid' => $user_id]);
        if (empty($user)) {
            $view = $this->view(Error::withMessage('User not found'), Response::HTTP_NOT_FOUND);
            return $this->handleView($view);
        }

        foreach ($clan->getUsers() as $userClan) {
            if ($userClan->getUser() === $user) {
                $view = $this->view(Error::withMessage('User already member'), Response::HTTP_OK);
                return $this->handleView($view);
            }
        }

        $userClan = new UserClan();
        $userClan->setClan($clan);
        $userClan->setUser($user);
        $userClan->setAdmin(false);
        $this->em->persist($userClan);
        $this->em->flush();

        $view = $this->view(null, Response::HTTP_NO_CONTENT);
        return $this->handleView($view);
    }

    /**
     * Gets Users of Clan
     *
     * @Rest\Get("/{uuid}/users", requirements= {"uuid"="[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}"})
     * @ParamConverter("clan", options={"mapping": {"uuid": "uuid"}})
     */
    public function getMemberAction(Clan $clan)
    {
        $result = array();
        foreach ($clan->getUsers() as $userClan) {
            $result[] = $userClan->getUser()->getUuid();
        }

        $view = $this->view($result, Response::HTTP_OK);
        return $this->handleView($view);
    }

    /**
     * Removes a User from a Clan.
     *
     * @Rest\Delete("/{uuid}/users/{user}", requirements= {
     *     "uuid"="[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}",
     *     "user"="[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}"}
     * )
     * @ParamConverter("clan", options={"mapping": {"uuid": "uuid"}})
     * @ParamConverter("user", options={"mapping": {"user": "uuid"}})
     */
    public function removeMemberAction(Clan $clan, User $user)
    {
        foreach ($clan->getUsers() as $userClan) {
            if ($userClan->getUser() === $user)
            $this->em->remove($userClan);
            break;
        }
        $this->em->flush();

        $view = $this->view(null, Response::HTTP_NO_CONTENT);
        return $this->handleView($view);
    }

    /**
     * Gets a User from a Clan.
     *
     * @Rest\Get("/{uuid}/users/{user}", requirements= {
     *     "uuid"="[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}",
     *     "user"="[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}"}
     * )
     * @ParamConverter("clan", options={"mapping": {"uuid": "uuid"}})
     * @ParamConverter("user", options={"mapping": {"user": "uuid"}})
     */
    public function getMemberOfClanAction(Clan $clan, User $user)
    {
        $user_ids = $clan->getUsers()->map(function (UserClan $uc) { return $uc->getUser()->getUuid(); })->toArray();
        if (!in_array($user->getUuid(), $user_ids)) {
            return $this->handleView($this->view(Error::withMessage("User not in clan"), Response::HTTP_NOT_FOUND));
        }
        return $this->redirectToRoute('app_rest_user_getuser', ["uuid" => $user->getUuid()]);
    }
}
