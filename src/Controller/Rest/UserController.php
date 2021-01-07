<?php

namespace App\Controller\Rest;

use App\Entity\Clan;
use App\Entity\User;
use App\Entity\UserClan;
use App\Repository\UserRepository;
use App\Serializer\ClanNormalizer;
use App\Service\UserService;
use App\Transfer\Error;
use App\Transfer\PaginationCollection;
use App\Transfer\Search;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcher;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Class UserController.
 *
 * @Rest\Route("/users")
 */
class UserController extends AbstractFOSRestController
{
    private EntityManagerInterface $em;
    private UserRepository $userRepository;
    private UserService $userService;
    private PasswordEncoderInterface $passwordEncoder;

    public function __construct(EntityManagerInterface $entityManager, UserRepository $userRepository, UserService $userService, PasswordEncoderInterface $passwordEncoder)
    {
        $this->em = $entityManager;
        $this->userRepository = $userRepository;
        $this->userService = $userService;
        $this->passwordEncoder = $passwordEncoder;
    }

    /**
     * @Rest\Get("/{uuid}", requirements= {"uuid"="([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})"})
     * @ParamConverter()
     */
    public function getUserAction(User $user)
    {
        // TODO check KLMS as this cannot handle email any more
        $view = $this->view($user);
        return $this->handleView($view);
    }

    /**
     * Edits a User.
     *
     * @Rest\Patch("/{uuid}", requirements= {"uuid"="[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}"})
     * @ParamConverter("user", class="App\Entity\User")
     * @ParamConverter("update", converter="fos_rest.request_body",
     *     options={
     *      "deserializationContext": {"allow_extra_attributes": false},
     *      "validator": {"groups": {"Transfer"} },
     *      "attribute_to_populate": "user",
     *     })
     */
    public function editUserAction(User $update, ConstraintViolationListInterface $validationErrors)
    {
        if (count($validationErrors) > 0) {
            $view = $this->view(Error::withMessageAndDetail("Invalid JSON Body supplied, please check the Documentation", $validationErrors[0]), Response::HTTP_BAD_REQUEST);
            return $this->handleView($view);
        }

        if ($this->passwordEncoder->needsRehash($update->getPassword())) {
            $update->setPassword($this->passwordEncoder->encodePassword($update->getPassword(), null));
        }

        $this->em->persist($update);
        $this->em->flush();

        return $this->handleView($this->view($update));
    }

    /**
     * Creates a User.
     *
     * @Rest\Post("")
     * @ParamConverter("new", converter="fos_rest.request_body",
     *     options={
     *      "deserializationContext": {"allow_extra_attributes": false},
     *      "validator": {"groups": {"Transfer", "Create"} }
     *     })
     */
    public function createUserAction(User $new, ConstraintViolationListInterface $validationErrors)
    {
        if (count($validationErrors) > 0) {
            $view = $this->view(Error::withMessageAndDetail("Invalid JSON Body supplied, please check the Documentation", $validationErrors[0]), Response::HTTP_BAD_REQUEST);
            return $this->handleView($view);
        }

        // TODO move this to UserService
        $new->setStatus(1);
        $new->setEmailConfirmed(false);
        $new->setInfoMails($new->getInfoMails() ?? false);
        $new->setPassword($this->passwordEncoder->encodePassword($new->getPassword(), null));

        $this->em->persist($new);
        $this->em->flush();

        return $this->handleView($this->view($new, Response::HTTP_CREATED));
    }

    /**
     * Returns multiple Userobjects.
     *
     * Supports searching via UUID
     *
     * @Rest\Post("/search")
     * @ParamConverter("search", converter="fos_rest.request_body", options={"deserializationContext": {"allow_extra_attributes": false}})
     */
    public function postUsersearchAction(Search $search, ConstraintViolationListInterface $validationErrors)
    {
        if (count($validationErrors) > 0) {
            $view = $this->view(Error::withMessageAndDetail("Invalid JSON Body supplied, please check the Documentation", $validationErrors[0]), Response::HTTP_BAD_REQUEST);
            return $this->handleView($view);
        }

        $user = $this->userRepository->findBySearch($search);

        $view = $this->view($user);
        return $this->handleView($view);
    }

    /**
     * Returns all User objects with filter.
     *
     * @Rest\Get("")
     * @Rest\QueryParam(name="page", requirements="\d+", default="1")
     * @Rest\QueryParam(name="limit", requirements="\d+", default="10")
     * @Rest\QueryParam(name="q", default="")
     */
    public function getUsersAction(ParamFetcher $fetcher)
    {
        $page = intval($fetcher->get('page'));
        $limit = intval($fetcher->get('limit'));
        $filter = $fetcher->get('q');

        // Select all Users where the Status is greater then 0 (e.g. not disabled/locked/deactivated)
        $qb = $this->userRepository->findAllActiveQueryBuilder($filter);
        $pager = new Pagerfanta(new QueryAdapter($qb));
        $pager->setMaxPerPage($limit);
        $pager->setCurrentPage($page);

        $users = array();
        foreach ($pager->getCurrentPageResults() as $user) {
            $users[] = $user;
        }

        $collection = new PaginationCollection(
            $users,
            $pager->getNbResults()
        );

        $view = $this->view($collection);
        return $this->handleView($view);
    }

    /**
     * Gets Clans of User
     *
     * @Rest\Get("/{uuid}/clans", requirements= {"uuid"="[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}"})
     * @ParamConverter("user", options={"mapping": {"uuid": "uuid"}})
     */
    public function getMemberAction(User $user)
    {
        $result = array();
        foreach ($user->getClans() as $userClan) {
            $result[] = $userClan->getClan();
        }
        $view = $this->view($result, Response::HTTP_OK);
        $view->getContext()->setAttribute(ClanNormalizer::UUID_ONLY, true);
        return $this->handleView($view);
    }

    /**
     * Gets a Clan from a User.
     *
     * @Rest\Get("/{uuid}/clans/{clan}", requirements= {
     *     "uuid"="[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}",
     *     "clan"="[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}"}
     * )
     * @ParamConverter("user", options={"mapping": {"uuid": "uuid"}})
     * @ParamConverter("clan", options={"mapping": {"clan": "uuid"}})
     */
    public function getClanOfMemberAction(User $user, Clan $clan)
    {
        $clan_ids = $user->getClans()->map(function (UserClan $uc) { return $uc->getClan()->getUuid(); })->toArray();
        if (!in_array($clan->getUuid(), $clan_ids)) {
            return $this->handleView($this->view(Error::withMessage("User not in clan"), Response::HTTP_NOT_FOUND));
        }
        return $this->redirectToRoute('app_rest_clan_getclan', ["uuid" => $clan->getUuid()]);
    }
}
