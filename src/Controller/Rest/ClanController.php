<?php

namespace App\Controller\Rest;

use App\Entity\Clan;
use App\Entity\User;
use App\Entity\UserClan;
use App\Repository\ClanRepository;
use App\Repository\UserRepository;
use App\Serializer\UserClanNormalizer;
use App\Service\ClanService;
use App\Transfer\AuthObject;
use App\Transfer\Bulk;
use App\Transfer\Error;
use App\Transfer\PaginationCollection;
use App\Transfer\UuidObject;
use App\Transfer\ValidationError;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcher;
use Nelmio\ApiDocBundle\Annotation\Model;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Swagger\Annotations as SWG;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Class ClanController.
 *
 * @Rest\Route("/clans")
 */
class ClanController extends AbstractFOSRestController
{
    private EntityManagerInterface $em;
    private ClanService $clanService;
    private ClanRepository $clanRepository;
    private UserRepository $userRepository;
    private PasswordHasherFactoryInterface $hasherFactory;

    public function __construct(
        EntityManagerInterface $entityManager,
        ClanService $clanService,
        ClanRepository $clanRepository,
        UserRepository $userRepository,
        PasswordHasherFactoryInterface $hasherFactory
    ) {
        $this->em = $entityManager;
        $this->clanService = $clanService;
        $this->clanRepository = $clanRepository;
        $this->userRepository = $userRepository;
        $this->hasherFactory = $hasherFactory;
    }

    private function handleValidiationErrors(ConstraintViolationListInterface $errors)
    {
        if (count($errors) == 0) {
            return null;
        }

        $error = $errors[0];
        if ($error->getConstraint() instanceof UniqueEntity) {
            return $this->view(ValidationError::withProperty($error->getPropertyPath(), 'UniqueEntity'), Response::HTTP_CONFLICT);
        } else {
            return $this->view(ValidationError::withProperty($error->getPropertyPath(), 'Assert'), Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Returns a single Clan object.
     *
     * @SWG\Response(
     *     response=200,
     *     description="Returns the Clan",
     *     schema=@SWG\Schema(type="object", ref=@Model(type=\App\Entity\Clan::class, groups={"read"}))
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Returns if the clan does not exitst"
     * )
     * @SWG\Parameter(
     *     name="uuid",
     *     type="string",
     *     in="path",
     *     description="the UUID of the clan to query",
     *     required=true,
     *     format="uuid"
     * )
     * @SWG\Tag(name="Clan")
     * @Rest\Get("/{uuid}", requirements= {"uuid"="[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}"})
     * @ParamConverter("clan", options={"mapping": {"uuid": "uuid"}})
     * @Rest\QueryParam(name="depth", requirements="\d+", allowBlank=false, default="2")
     */
    public function getClanAction(Clan $clan, ParamFetcher $fetcher)
    {
        $depth = intval($fetcher->get('depth'));
        $view = $this->view($clan);
        $view->getContext()->setAttribute(UserClanNormalizer::DEPTH, $depth);

        return $this->handleView($view);
    }

    /**
     * Creates a Clan.
     *
     * @SWG\Response(
     *     response=201,
     *     description="The edited clan",
     *     schema=@SWG\Schema(type="object", ref=@Model(type=\App\Entity\Clan::class, groups={"read"}))
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Returns if the body was invalid"
     * )
     * @SWG\Response(
     *     response=409,
     *     description="Returns if the body was invalid"
     * )
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     description="the updated clan field as JSON",
     *     required=true,
     *     format="application/json",
     *     schema=@SWG\Schema(type="object", ref=@Model(type=\App\Entity\Clan::class, groups={"write"}))
     * )
     * @SWG\Tag(name="Clan")
     * @Rest\Post("")
     * @ParamConverter("new", converter="fos_rest.request_body",
     *     options={
     *      "deserializationContext": {"allow_extra_attributes": false},
     *      "validator": {"groups": {"Transfer", "Create", "Unique"} }
     *     })
     */
    public function createClanAction(Clan $new, ConstraintViolationListInterface $validationErrors)
    {
        if (count($validationErrors) > 0) {
            $error = $validationErrors[0];
            if ($error->getConstraint() instanceof UniqueEntity) {
                $view = $this->view(Error::withMessageAndDetail('There is already an object with the same unique values', $error), Response::HTTP_CONFLICT);
            } else {
                $view = $this->view(Error::withMessageAndDetail('Invalid JSON Body supplied, please check the Documentation', $error), Response::HTTP_BAD_REQUEST);
            }

            return $this->handleView($view);
        }

        $hasher = $this->hasherFactory->getPasswordHasher(Clan::class);
        $new->setJoinPassword($hasher->hash($new->getJoinPassword()));

        $this->em->persist($new);
        $this->em->flush();

        $view = $this->view($new, Response::HTTP_CREATED);

        return $this->handleView($view);
    }

    /**
     * Edits a clan.
     *
     * @SWG\Response(
     *     response=200,
     *     description="The edited clan",
     *     schema=@SWG\Schema(type="object", ref=@Model(type=\App\Entity\Clan::class, groups={"read"}))
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Returns if the clan does not exitst"
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Returns if the body was invalid"
     * )
     * @SWG\Parameter(
     *     name="uuid",
     *     type="string",
     *     in="path",
     *     description="the UUID of the clan to modify",
     *     required=true,
     *     format="uuid"
     * )
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     description="the updated clan field as JSON",
     *     required=true,
     *     format="application/json",
     *     schema=@SWG\Schema(type="object", ref=@Model(type=\App\Entity\Clan::class, groups={"write"}))
     * )
     * @SWG\Tag(name="Clan")
     * @Rest\Patch("/{uuid}", requirements= {"uuid"="[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}"})
     * @ParamConverter("clan", class="App\Entity\Clan")
     * @ParamConverter("update", converter="fos_rest.request_body",
     *     options={
     *      "deserializationContext": {"allow_extra_attributes": false},
     *      "validator": {"groups": {"Transfer", "Unique"} },
     *      "attribute_to_populate": "clan",
     *     })
     */
    public function editClanAction(Clan $update, ConstraintViolationListInterface $validationErrors)
    {
        if ($view = $this->handleValidiationErrors($validationErrors)) {
            return $this->handleView($view);
        }

        $hasher = $this->hasherFactory->getPasswordHasher(Clan::class);

        if ($hasher->needsRehash($update->getJoinPassword())) {
            $update->setJoinPassword($hasher->hash($update->getJoinPassword()));
        }

        $this->em->persist($update);
        $this->em->flush();

        return $this->handleView($this->view($update));
    }

    /**
     * Delete a Clan.
     *
     * @Rest\Delete("/{uuid}", requirements= {"uuid"="[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}"})
     * @ParamConverter("clan", options={"mapping": {"uuid": "uuid"}})
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
     * @Rest\QueryParam(name="filter")
     * @Rest\QueryParam(name="sort", requirements="(asc|desc)", map=true)
     * @Rest\QueryParam(name="exact", requirements="(true|false)", allowBlank=false, default="false")
     * @Rest\QueryParam(name="case", requirements="(true|false)", allowBlank=false, default="false")
     * @Rest\QueryParam(name="depth", requirements="\d+", allowBlank=false, default="2")
     */
    public function getClansAction(ParamFetcher $fetcher)
    {
        $page = intval($fetcher->get('page'));
        $limit = intval($fetcher->get('limit'));
        $filter = $fetcher->get('filter');
        $sort = $fetcher->get('sort');
        $exact = $fetcher->get('exact');
        $case = $fetcher->get('case');
        $depth = intval($fetcher->get('depth'));

        $sort = is_array($sort) ? $sort : (empty($sort) ? [] : [$sort => 'asc']);
        $case = $case === 'true';
        $exact = $exact === 'true';

        if (is_array($filter)) {
            $qb = $this->clanRepository->findAllQueryBuilder($filter, $sort, $case, $exact);
        } else {
            $qb = $this->clanRepository->findAllSimpleQueryBuilder($filter, $sort, $case, $exact);
        }

        // set useOutputWalker to false otherwise we cannot Paginate Entities with INNER/LEFT Joins
        $pager = new Pagerfanta(new QueryAdapter($qb, true, false));
        $pager->setMaxPerPage($limit);
        $pager->setCurrentPage($page);

        $clans = [];
        foreach ($pager->getCurrentPageResults() as $clan) {
            $clans[] = $clan;
        }

        $collection = new PaginationCollection(
            $clans,
            $pager->getNbResults()
        );

        $view = $this->view($collection);
        $view->getContext()->setAttribute(UserClanNormalizer::DEPTH, $depth);

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
        if ($view = $this->handleValidiationErrors($validationErrors)) {
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
        if ($view = $this->handleValidiationErrors($validationErrors)) {
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
     * Gets Users of Clan.
     *
     * @Rest\Get("/{uuid}/users", requirements= {"uuid"="[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}"})
     * @ParamConverter("clan", options={"mapping": {"uuid": "uuid"}})
     * @Rest\QueryParam(name="depth", requirements="\d+", allowBlank=false, default="1")
     */
    public function getMemberAction(Clan $clan, ParamFetcher $fetcher)
    {
        $result = [];
        foreach ($clan->getUsers() as $userClan) {
            $result[] = $userClan->getUser();
        }

        $view = $this->view($result, Response::HTTP_OK);
        $view->getContext()->setAttribute(UserClanNormalizer::DEPTH, intval($fetcher->get('depth')));

        return $this->handleView($view);
    }

    /**
     * Gets Users of Clan.
     *
     * @Rest\Get("/{uuid}/admins", requirements= {"uuid"="[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}"})
     * @ParamConverter("clan", options={"mapping": {"uuid": "uuid"}})
     * @Rest\QueryParam(name="depth", requirements="\d+", allowBlank=false, default="1")
     */
    public function getAdminAction(Clan $clan, ParamFetcher $fetcher)
    {
        $result = [];
        foreach ($clan->getUsers() as $userClan) {
            if ($userClan->getAdmin()) {
                $result[] = $userClan->getUser();
            }
        }

        $view = $this->view($result, Response::HTTP_OK);
        $view->getContext()->setAttribute(UserClanNormalizer::DEPTH, intval($fetcher->get('depth')));

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
            return $this->handleView($this->view(Error::withMessage('User not in clan'), Response::HTTP_NOT_FOUND));
        }

        return $this->redirectToRoute('app_rest_user_getuser', ['uuid' => $user->getUuid()]);
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
            return $this->handleView($this->view(Error::withMessage('User not admin of clan'), Response::HTTP_NOT_FOUND));
        }

        return $this->redirectToRoute('app_rest_user_getuser', ['uuid' => $user->getUuid()]);
    }

    /**
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
     * @return bool True if user is member and status was changed, false otherwise
     */
    private function UserSetAdmin(Clan $clan, User $user, bool $admin): bool
    {
        foreach ($clan->getUsers() as $userClan) {
            if ($userClan->getUser() === $user) {
                if ($admin === $userClan->getAdmin()) {
                    return false;
                }
                $userClan->setAdmin($admin);
                $this->em->persist($userClan);
                $this->em->flush();

                return true;
            }
        }

        return false;
    }

    /**
     * Checks if the Clan credentials are correct.
     *
     * Checks Username/Password against the Database and returns the user if credentials are valid
     *
     * @SWG\Response(
     *     response=200,
     *     description="Returns the Clan",
     *     schema=@SWG\Schema(type="object", ref=@Model(type=\App\Entity\Clan::class, groups={"read"}))
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Returns if no Name and/or Password could be found"
     * )
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     description="credentials as JSON",
     *     required=true,
     *     format="application/json",
     *     schema=@SWG\Schema(type="object", ref=@Model(type=\App\Transfer\AuthObject::class))
     * )
     * @SWG\Tag(name="Authorization")
     * @Rest\Post("/authorize")
     * @ParamConverter("auth", converter="fos_rest.request_body", options={"deserializationContext": {"allow_extra_attributes": false}})
     */
    public function postAuthorizeAction(AuthObject $auth, ConstraintViolationListInterface $validationErrors)
    {
        if ($view = $this->handleValidiationErrors($validationErrors)) {
            return $this->handleView($view);
        }

        // Check if User can login
        $clan = $this->clanService->checkCredentials($auth->name, $auth->secret);

        if ($clan) {
            $view = $this->view($clan);
        } else {
            $view = $this->view(Error::withMessage('Invalid credentials'), Response::HTTP_NOT_FOUND);
        }

        return $this->handleView($view);
    }

    /**
     * Requests multiple clans by their uuids.
     *
     * Post a Bulk Request object to get a response object.
     *
     * @SWG\Response(
     *     response=200,
     *     description="Returns the requested Clans",
     *     schema=@SWG\Schema(type="array", ref=@Model(type=\App\Entity\Clan::class, groups={"read"}))
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Returns the request was malformated"
     * )
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     description="JSON array of clan UUIDs",
     *     required=true,
     *     format="application/json",
     *     schema=@SWG\Schema(type="object", ref=@Model(type=\App\Transfer\Bulk::class))
     * )
     * @Rest\Post("/bulk")
     * @ParamConverter("bulk", converter="fos_rest.request_body", options={"deserializationContext": {"allow_extra_attributes": false}})
     */
    public function postBulkRequestAction(Bulk $bulk, ConstraintViolationListInterface $validationErrors)
    {
        if ($view = $this->handleValidiationErrors($validationErrors)) {
            return $this->handleView($view);
        }

        $data = $this->clanRepository->findByBulk($bulk);

        return $this->handleView($this->view($data));
    }
}
