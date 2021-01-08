<?php

namespace App\Controller\Rest;

use App\Entity\Clan;
use App\Entity\User;
use App\Entity\UserClan;
use App\Repository\ClanRepository;
use App\Repository\UserClanRepository;
use App\Repository\UserRepository;
use App\Serializer\UserNormalizer;
use App\Transfer\Error;
use App\Transfer\PaginationCollection;
use App\Transfer\UuidObject;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcher;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Swagger\Annotations as SWG;
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
     * Returns a single Clan object.
     *
     * @SWG\Tag(name="Clan")
     * @SWG\Response(
     *     response=200,
     *     description="Returns Clan object"
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Returns if no clan can be found with uuid could be found"
     * )
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
     * @SWG\Tag(name="Clan")
     * @SWG\Response(
     *     response=201,
     *     description="The clan was created"
     * )
     * @SWG\Response(
     *     response=400,
     *     description="If an anvalid request was made (e.g. missing fields, wrong types)"
     * )
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
     * @ParamConverter("user_uuid", converter="fos_rest.request_body")
     */
    public function addMemberAction(Clan $clan, UuidObject $user_uuid, ConstraintViolationListInterface $validationErrors)
    {
        if (count($validationErrors) > 0) {
            $view = $this->view(Error::withMessageAndDetail('Invalid JSON Body supplied, please check the Documentation', $validationErrors[0]), Response::HTTP_BAD_REQUEST);
            return $this->handleView($view);
        }

        $user = $this->userRepository->findOneBy(['uuid' => $user_uuid->uuid]);
        if (empty($user)) {
            $view = $this->view(Error::withMessage('User not found'), Response::HTTP_NOT_FOUND);
            return $this->handleView($view);
        }

        if ($this->UserJoin($clan, $user)) {
            $view = $this->view(null, Response::HTTP_NO_CONTENT);
        } else {
            $view = $this->view(Error::withMessage('User already member'), Response::HTTP_OK);
        }
        return $this->handleView($view);
    }

    /**
     * Adds a User to a Clan.
     *
     * @Rest\Post("/{uuid}/admins", requirements= {"uuid"="[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}"})
     * @ParamConverter("clan", options={"mapping": {"uuid": "uuid"}})
     * @ParamConverter("user_uuid", converter="fos_rest.request_body")
     */
    public function addAdminAction(Clan $clan, UuidObject $user_uuid, ConstraintViolationListInterface $validationErrors)
    {
        if (count($validationErrors) > 0) {
            $view = $this->view(Error::withMessageAndDetail('Invalid JSON Body supplied, please check the Documentation', $validationErrors[0]), Response::HTTP_BAD_REQUEST);
            return $this->handleView($view);
        }

        $user = $this->userRepository->findOneBy(['uuid' => $user_uuid->uuid]);
        if (empty($user)) {
            $view = $this->view(Error::withMessage('User not found'), Response::HTTP_NOT_FOUND);
            return $this->handleView($view);
        }

        if ($this->UserSetAdmin($clan, $user, true) || $this->UserJoin($clan, $user, true)) {
            $view = $this->view(null, Response::HTTP_NO_CONTENT);
        } else {
            $view = $this->view(Error::withMessage('User already admin'), Response::HTTP_OK);
        }
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
            $result[] = $userClan->getUser();
        }

        $view = $this->view($result, Response::HTTP_OK);
        $view->getContext()->setAttribute(UserNormalizer::UUID_ONLY, true);
        return $this->handleView($view);
    }

    /**
     * Gets Users of Clan
     *
     * @Rest\Get("/{uuid}/admins", requirements= {"uuid"="[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}"})
     * @ParamConverter("clan", options={"mapping": {"uuid": "uuid"}})
     */
    public function getAdminAction(Clan $clan)
    {
        $result = array();
        foreach ($clan->getUsers() as $userClan) {
            if ($userClan->getAdmin())
                $result[] = $userClan->getUser();
        }

        $view = $this->view($result, Response::HTTP_OK);
        $view->getContext()->setAttribute(UserNormalizer::UUID_ONLY, true);
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
        if ($this->UserLeave($clan, $user)) {
            $view = $this->view(null, Response::HTTP_NO_CONTENT);
        } else {
            $view = $this->view(Error::withMessage('User not member'), Response::HTTP_NOT_FOUND);
        }
        return $this->handleView($view);
    }

    /**
     * Removes a Admin from a Clan.
     *
     * @Rest\Delete("/{uuid}/admins/{user}", requirements= {
     *     "uuid"="[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}",
     *     "user"="[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}"}
     * )
     * @ParamConverter("clan", options={"mapping": {"uuid": "uuid"}})
     * @ParamConverter("user", options={"mapping": {"user": "uuid"}})
     */
    public function removeAdminAction(Clan $clan, User $user)
    {
        if ($this->UserSetAdmin($clan, $user, false)) {
            $view = $this->view(null, Response::HTTP_NO_CONTENT);
        } else {
            $view = $this->view(Error::withMessage('User not admin'), Response::HTTP_NOT_FOUND);
        }
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
        $user_ids = $clan->getUsers()
            ->map(function (UserClan $uc) { return $uc->getUser()->getUuid(); })
            ->toArray();
        if (!in_array($user->getUuid(), $user_ids)) {
            return $this->handleView($this->view(Error::withMessage("User not in clan"), Response::HTTP_NOT_FOUND));
        }
        return $this->redirectToRoute('app_rest_user_getuser', ["uuid" => $user->getUuid()]);
    }

    /**
     * Gets a User from a Clan.
     *
     * @Rest\Get("/{uuid}/admins/{user}", requirements= {
     *     "uuid"="[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}",
     *     "user"="[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}"}
     * )
     * @ParamConverter("clan", options={"mapping": {"uuid": "uuid"}})
     * @ParamConverter("user", options={"mapping": {"user": "uuid"}})
     */
    public function getAdminOfClanAction(Clan $clan, User $user)
    {
        $user_ids = $clan->getUsers()
            ->filter(function (UserClan $uc) { return $uc->getAdmin(); })
            ->map(function (UserClan $uc) { return $uc->getUser()->getUuid(); })
            ->toArray();
        if (!in_array($user->getUuid(), $user_ids)) {
            return $this->handleView($this->view(Error::withMessage("User not admin of clan"), Response::HTTP_NOT_FOUND));
        }
        return $this->redirectToRoute('app_rest_user_getuser', ["uuid" => $user->getUuid()]);
    }

    /**
     * @param Clan $clan
     * @param User $user
     * @param bool $admin
     * @return bool True if user was joined, false otherwise
     */
    private function UserJoin(Clan $clan, User $user, bool $admin = false): bool
    {
        foreach ($clan->getUsers() as $userClan) {
            if ($userClan->getUser() === $user) {
                return false;
            }
        }

        $userClan = new UserClan();
        $userClan->setClan($clan);
        $userClan->setUser($user);
        $userClan->setAdmin($admin);
        $this->em->persist($userClan);
        $this->em->flush();

        return true;
    }

    /**
     * @param Clan $clan
     * @param User $user
     * @return bool True if user was removed, false otherwise
     */
    private function UserLeave(Clan $clan, User $user): bool
    {
        foreach ($clan->getUsers() as $userClan) {
            if ($userClan->getUser() === $user) {
                $this->em->remove($userClan);
                $this->em->flush();
                return true;
            }
        }
        return false;
    }

    /**
     * @param Clan $clan
     * @param User $user
     * @param bool $admin
     * @return bool True if user is member and status was changed, false otherwise
     */
    private function UserSetAdmin(Clan $clan, User $user, bool $admin): bool
    {
        foreach ($clan->getUsers() as $userClan) {
            if ($userClan->getUser() === $user) {
                if ($admin === $userClan->getAdmin())
                    return false;
                $userClan->setAdmin($admin);
                $this->em->persist($userClan);
                $this->em->flush();
                return true;
            }
        }
        return false;
    }
}
