<?php

namespace App\Controller\Rest;

use App\Entity\Clan;
use App\Entity\User;
use App\Entity\UserClan;
use App\Repository\UserRepository;
use App\Serializer\UserClanNormalizer;
use App\Service\UserService;
use App\Transfer\AuthObject;
use App\Transfer\Bulk;
use App\Transfer\Error;
use App\Transfer\PaginationCollection;
use App\Transfer\Search;
use App\Transfer\ValidationError;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcher;
use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\Model;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use OpenApi\Annotations as OA;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Class UserController.
 */
#[Rest\Route('/users')]
class UserController extends AbstractFOSRestController
{
    public function __construct(private readonly EntityManagerInterface $em, private readonly UserRepository $userRepository, private readonly UserService $userService, private readonly PasswordHasherFactoryInterface $hasherFactory)
    {
    }

    private function handleValidiationErrors(ConstraintViolationListInterface $errors): ?View
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
     * Gets a User object.
     *
     * @OA\Response(
     *     response=200,
     *     description="Returns the User",
     *     @OA\Schema(type="object", ref=@Model(type=\App\Entity\User::class, groups={"read"}))
     * )
     * @OA\Response(
     *     response=404,
     *     description="Returns if the user does not exitst"
     * )
     * @OA\Parameter(
     *     name="uuid",
     *     in="path",
     *     description="the UUID of the user to query",
     *     required=true,
     *     @OA\Schema(type="string", format="uuid")
     * )
     * @OA\Tag(name="User")
     */
    #[Rest\Get('/{uuid}', requirements: ['uuid' => '([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})'])]
    #[Rest\QueryParam(name: 'depth', requirements: '\d+', default: 2, allowBlank: false)]
    #[ParamConverter('user', options: ['mapping' => ['uuid' => 'uuid']])]
    public function getUserAction(User $user, ParamFetcher $fetcher): Response
    {
        $depth = intval($fetcher->get('depth'));
        $view = $this->view($user);
        $view->getContext()->setAttribute(UserClanNormalizer::DEPTH, $depth);

        return $this->handleView($view);
    }

    /**
     * Edits a User.
     *
     * @OA\Response(
     *     response=200,
     *     description="The edited user",
     *     @OA\Schema(type="object", ref=@Model(type=\App\Entity\User::class, groups={"read"}))
     * )
     * @OA\Response(
     *     response=404,
     *     description="Returns if the user does not exitst"
     * )
     * @OA\Response(
     *     response=400,
     *     description="Returns if the body was invalid"
     * )
     * @OA\Parameter(
     *     name="uuid",
     *     in="path",
     *     description="the UUID of the user to modify",
     *     required=true,
     *     @OA\Schema(type="string", format="uuid")     * )
     * @OA\RequestBody(
     *     description="the updated user field as JSON",
     *     required=true,
     *     @OA\Schema(type="object", format="application/json", ref=@Model(type=\App\Entity\User::class, groups={"write"}))
     * )
     * @OA\Tag(name="User")
     */
    #[Rest\Patch('/{uuid}', requirements: ['uuid' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'])]
    #[ParamConverter('user', class: 'App\Entity\User')]
    #[ParamConverter('update', options: ['deserializationContext' => ['allow_extra_attributes' => false], 'validator' => ['groups' => ['Transfer', 'Unique']], 'attribute_to_populate' => 'user'], converter: 'fos_rest.request_body')]
    public function editUserAction(User $update, ConstraintViolationListInterface $validationErrors): Response
    {
        if ($view = $this->handleValidiationErrors($validationErrors)) {
            return $this->handleView($view);
        }

        $hasher = $this->hasherFactory->getPasswordHasher(User::class);

        if ($hasher->needsRehash($update->getPassword())) {
            $update->setPassword($hasher->hash($update->getPassword()));
        }

        $this->em->persist($update);
        $this->em->flush();

        return $this->handleView($this->view($update));
    }

    /**
     * Creates a User.
     *
     * @OA\Response(
     *     response=201,
     *     description="The created user",
     *     @OA\Schema(type="object", ref=@Model(type=\App\Entity\User::class, groups={"read"}))
     * )
     * @OA\Response(
     *     response=400,
     *     description="Returns if the body was invalid"
     * )
     * @OA\RequestBody(
     *     description="the updated user field as JSON",
     *     required=true,
     *     @OA\Schema(type="object", format="application/json", ref=@Model(type=\App\Entity\User::class, groups={"write"}))
     * )
     * @OA\Tag(name="User")
     */
    #[Rest\Post('')]
    #[ParamConverter('new', options: ['deserializationContext' => ['allow_extra_attributes' => false], 'validator' => ['groups' => ['Transfer', 'Create', 'Unique']]], converter: 'fos_rest.request_body')]
    public function createUserAction(User $new, ConstraintViolationListInterface $validationErrors): Response
    {
        if ($view = $this->handleValidiationErrors($validationErrors)) {
            return $this->handleView($view);
        }

        $hasher = $this->hasherFactory->getPasswordHasher(User::class);

        // TODO move this to UserService
        $new->setStatus(1);
        $new->setEmailConfirmed(false);
        $new->setInfoMails($new->getInfoMails() ?? false);
        $new->setPassword($hasher->hash($new->getPassword()));

        $this->em->persist($new);
        $this->em->flush();

        return $this->handleView($this->view($new, Response::HTTP_CREATED));
    }

    /**
     * Returns multiple Userobjects.
     *
     * Supports searching via UUID
     */
    #[Rest\Post('/search')]
    #[ParamConverter('search', options: ['deserializationContext' => ['allow_extra_attributes' => false]], converter: 'fos_rest.request_body')]
    public function postUsersearchAction(Search $search, ConstraintViolationListInterface $validationErrors): Response
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
     * @OA\Response(
     *     response=200,
     *     description="Returns the User",
     *     @OA\Schema(type="object", ref=@Model(type=\App\Entity\User::class, groups={"read"}))
     * )
     * @OA\Response(
     *     response=404,
     *     description="Returns if no EMail and/or Password could be found"
     * )
     * @OA\RequestBody(
     *     description="credentials as JSON",
     *     required=true,
     *     @OA\Schema(type="object", format="application/json", ref=@Model(type=\App\Transfer\AuthObject::class))
     * )
     * @OA\Tag(name="Authorization")
     */
    #[Rest\Post('/authorize')]
    #[ParamConverter('auth', options: ['deserializationContext' => ['allow_extra_attributes' => false]], converter: 'fos_rest.request_body')]
    public function postAuthorizeAction(AuthObject $auth, ConstraintViolationListInterface $validationErrors): Response
    {
        if ($view = $this->handleValidiationErrors($validationErrors)) {
            return $this->handleView($view);
        }

        // Check if User can login
        $user = $this->userService->checkCredentials($auth->name, $auth->secret);

        if ($user) {
            $view = $this->view($user);
        } else {
            $view = $this->view(Error::withMessage('Invalid credentials'), Response::HTTP_NOT_FOUND);
        }

        return $this->handleView($view);
    }

    /**
     * Requests multiple clans by their uuids.
     *
     * Post a Bulk Request object to get a response object.
     */
    #[Rest\Post('/bulk')]
    #[ParamConverter('bulk', options: ['deserializationContext' => ['allow_extra_attributes' => false]], converter: 'fos_rest.request_body')]
    public function postBulkRequestAction(Bulk $bulk, ConstraintViolationListInterface $validationErrors): Response
    {
        if ($view = $this->handleValidiationErrors($validationErrors)) {
            return $this->handleView($view);
        }

        $data = $this->userRepository->findByBulk($bulk);

        return $this->handleView($this->view($data));
    }

    /**
     * Returns all User objects with filter.
     */
    #[Rest\Get('')]
    #[Rest\QueryParam(name: 'page', requirements: '\d+', default: 1)]
    #[Rest\QueryParam(name: 'limit', requirements: '\d+', default: 10)]
    #[Rest\QueryParam(name: 'filter')]
    #[Rest\QueryParam(name: 'sort', requirements: '(asc|desc)', map: true)]
    #[Rest\QueryParam(name: 'exact', requirements: '(true|false)', default: false, allowBlank: false)]
    #[Rest\QueryParam(name: 'case', requirements: '(true|false)', default: false, allowBlank: false)]
    #[Rest\QueryParam(name: 'depth', requirements: '\d+', default: 2, allowBlank: false)]
    public function getUsersAction(ParamFetcher $fetcher): Response
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
            $qb = $this->userRepository->findAllQueryBuilder($filter, $sort, $case, $exact);
        } else {
            $qb = $this->userRepository->findAllSimpleQueryBuilder($filter, $sort, $case, $exact);
        }

        // Select all Users where the Status is greater then 0 (e.g. not disabled/locked/deactivated)
        $pager = new Pagerfanta(new QueryAdapter($qb, false));
        $pager->setMaxPerPage($limit);
        $pager->setCurrentPage($page);

        $users = [];
        foreach ($pager->getCurrentPageResults() as $user) {
            $users[] = $user;
        }

        $collection = new PaginationCollection(
            $users,
            $pager->getNbResults()
        );

        $view = $this->view($collection);
        $view->getContext()->setAttribute(UserClanNormalizer::DEPTH, $depth);

        return $this->handleView($view);
    }

    /**
     * Gets Clans of User.
     */
    #[Rest\Get('/{uuid}/clans', requirements: ['uuid' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'])]
    #[Rest\QueryParam(name: 'depth', requirements: '\d+', default: 1, allowBlank: false)]
    #[ParamConverter('user', options: ['mapping' => ['uuid' => 'uuid']])]
    public function getMemberAction(User $user, ParamFetcher $fetcher): Response
    {
        $result = [];
        foreach ($user->getClans() as $userClan) {
            $result[] = $userClan->getClan();
        }
        $view = $this->view($result, Response::HTTP_OK);
        $view->getContext()->setAttribute(UserClanNormalizer::DEPTH, intval($fetcher->get('depth')));

        return $this->handleView($view);
    }

    /**
     * Gets a Clan from a User.
     */
    #[Rest\Get('/{uuid}/clans/{clan}', requirements: ['uuid' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}', 'clan' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'])]
    #[ParamConverter('user', options: ['mapping' => ['uuid' => 'uuid']])]
    #[ParamConverter('clan', options: ['mapping' => ['clan' => 'uuid']])]
    public function getClanOfMemberAction(User $user, Clan $clan): RedirectResponse|Response
    {
        $clan_ids = $user->getClans()->map(fn (UserClan $uc) => $uc->getClan()->getUuid())->toArray();
        if (!in_array($clan->getUuid(), $clan_ids)) {
            return $this->handleView($this->view(Error::withMessage('User not in clan'), Response::HTTP_NOT_FOUND));
        }

        return $this->redirectToRoute('app_rest_clan_getclan', ['uuid' => $clan->getUuid()]);
    }
}
