<?php

namespace App\Controller\Rest;

use App\Entity\Clan;
use App\Entity\User;
use App\Entity\UserClan;
use App\Repository\UserRepository;
use App\Serializer\UserClanNormalizer;
use App\Service\UserService;
use App\Transfer\Error;
use App\Transfer\AuthObject;
use App\Transfer\PaginationCollection;
use App\Transfer\Search;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcher;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
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

    private function handleValidiationErrors(ConstraintViolationListInterface $errors)
    {
        if (count($errors) == 0)
            return null;

        $error = $errors[0];
        if ($error->getConstraint() instanceof UniqueEntity){
            return $this->view(Error::withMessageAndDetail("There is already an object with the same unique values", $error), Response::HTTP_CONFLICT);
        } else {
            return $this->view(Error::withMessageAndDetail("Invalid JSON Body supplied, please check the Documentation", $error), Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Gets a User object.
     *
     * @SWG\Response(
     *     response=200,
     *     description="Returns the User",
     *     schema=@SWG\Schema(type="object", ref=@Model(type=\App\Entity\User::class, groups={"read"}))
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Returns if the user does not exitst"
     * )
     * @SWG\Parameter(
     *     name="uuid",
     *     type="string",
     *     in="path",
     *     description="the UUID of the user to query",
     *     required=true,
     *     format="uuid"
     * )
     * @SWG\Tag(name="User")
     *
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
     * @SWG\Response(
     *     response=200,
     *     description="The edited user",
     *     schema=@SWG\Schema(type="object", ref=@Model(type=\App\Entity\User::class, groups={"read"}))
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Returns if the user does not exitst"
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Returns if the body was invalid"
     * )
     * @SWG\Parameter(
     *     name="uuid",
     *     type="string",
     *     in="path",
     *     description="the UUID of the user to modify",
     *     required=true,
     *     format="uuid"
     * )
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     description="the updated user field as JSON",
     *     required=true,
     *     format="application/json",
     *     schema=@SWG\Schema(type="object", ref=@Model(type=\App\Entity\User::class, groups={"write"}))
     * )
     * @SWG\Tag(name="User")
     *
     * @Rest\Patch("/{uuid}", requirements= {"uuid"="[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}"})
     * @ParamConverter("user", class="App\Entity\User")
     * @ParamConverter("update", converter="fos_rest.request_body",
     *     options={
     *      "deserializationContext": {"allow_extra_attributes": false},
     *      "validator": {"groups": {"Transfer", "Unique"} },
     *      "attribute_to_populate": "user",
     *     })
     */
    public function editUserAction(User $update, ConstraintViolationListInterface $validationErrors)
    {
        if ($view = $this->handleValidiationErrors($validationErrors)) {
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
     * @SWG\Response(
     *     response=201,
     *     description="The created user",
     *     schema=@SWG\Schema(type="object", ref=@Model(type=\App\Entity\User::class, groups={"read"}))
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Returns if the body was invalid"
     * )
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     description="the updated user field as JSON",
     *     required=true,
     *     format="application/json",
     *     schema=@SWG\Schema(type="object", ref=@Model(type=\App\Entity\User::class, groups={"write"}))
     * )
     * @SWG\Tag(name="User")
     *
     * @Rest\Post("")
     * @ParamConverter("new", converter="fos_rest.request_body",
     *     options={
     *      "deserializationContext": {"allow_extra_attributes": false},
     *      "validator": {"groups": {"Transfer", "Create", "Unique"} }
     *     })
     */
    public function createUserAction(User $new, ConstraintViolationListInterface $validationErrors)
    {
        if ($view = $this->handleValidiationErrors($validationErrors)) {
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
        if ($view = $this->handleValidiationErrors($validationErrors)) {
            return $this->handleView($view);
        }

        $user = $this->userRepository->findBySearch($search);

        $view = $this->view($user);
        return $this->handleView($view);
    }

    /**
     * Checks if the User is allowed to Login.
     *
     * Checks Username/Password against the Database and returns the user if credentials are valid
     *
     * @SWG\Response(
     *     response=200,
     *     description="Returns the User",
     *     schema=@SWG\Schema(type="object", ref=@Model(type=\App\Entity\User::class, groups={"read"}))
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Returns if no EMail and/or Password could be found"
     * )
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     description="credentials as JSON",
     *     required=true,
     *     format="application/json",
     *     schema=@SWG\Schema(type="object", ref=@Model(type=\App\Transfer\Login::class))
     * )
     * @SWG\Tag(name="Authorization")
     *
     * @Rest\Post("/authorize")
     * @ParamConverter("auth", converter="fos_rest.request_body", options={"deserializationContext": {"allow_extra_attributes": false}})
     */
    public function postAuthorizeAction(AuthObject $auth, ConstraintViolationListInterface $validationErrors)
    {
        if ($view = $this->handleValidiationErrors($validationErrors)) {
            return $this->handleView($view);
        }

        //Check if User can login
        $user = $this->userService->checkCredentials($auth->name, $auth->secret);

        if ($user) {
            $view = $this->view($user);
        } else {
            $view = $this->view(Error::withMessage('Invalid credentials'), Response::HTTP_NOT_FOUND);
        }

        return $this->handleView($view);
    }

    /**
     * Returns all User objects with filter.
     *
     * @Rest\Get("")
     * @Rest\QueryParam(name="page", requirements="\d+", default="1")
     * @Rest\QueryParam(name="limit", requirements="\d+", default="10")
     * @Rest\QueryParam(name="filter")
     * @Rest\QueryParam(name="sort", requirements="(asc|desc)", map=true)
     * @Rest\QueryParam(name="exact", requirements="(true|false)", allowBlank=false, default="false")
     */
    public function getUsersAction(ParamFetcher $fetcher)
    {
        $page = intval($fetcher->get('page'));
        $limit = intval($fetcher->get('limit'));
        $filter = $fetcher->get('filter');
        $sort = $fetcher->get('sort');
        $exact = $fetcher->get('exact');

        $sort = is_array($sort) ? $sort : (empty($sort) ? [] : [$sort => 'asc']);
        $exact = $exact === 'true';

        if (is_array($filter)) {
            $qb = $this->userRepository->findAllQueryBuilder($filter, $sort, $exact);
        } else {
            $qb = $this->userRepository->findAllSimpleQueryBuilder($filter, $sort, $exact);
        }

        // Select all Users where the Status is greater then 0 (e.g. not disabled/locked/deactivated)
        $pager = new Pagerfanta(new QueryAdapter($qb, false));
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
        $view->getContext()->setAttribute(UserClanNormalizer::DEPTH, 0);
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
