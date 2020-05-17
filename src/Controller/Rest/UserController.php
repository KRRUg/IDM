<?php

namespace App\Controller\Rest;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Form\UserEditType;
use App\Repository\UserRepository;
use App\Service\LoginService;
use App\Transfer\Error;
use App\Transfer\Login;
use App\Transfer\PaginationCollection;
use App\Transfer\Search;
use App\Transfer\UserAvailability;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\Prefix;
use FOS\RestBundle\Request\ParamFetcher;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Class UserController.
 *
 * @Prefix("/users")
 * @NamePrefix("rest_users_")
 */
class UserController extends AbstractFOSRestController
{
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var LoginService
     */
    private $loginService;
    /**
     * @var UserRepository
     */
    private UserRepository $userRepository;

    public function __construct(EntityManagerInterface $entityManager, LoginService $loginService, UserRepository $userRepository)
    {
        $this->em = $entityManager;
        $this->loginService = $loginService;
        $this->userRepository = $userRepository;
    }

    /**
     * Registers the User.
     *
     * @SWG\Response(
     *         response="200",
     *         description="Returned when successful"
     *     ),
     * @SWG\Response(
     *         response="400",
     *         description="Returned on a missing request parameter"
     *     ),
     * @SWG\Response(
     *         response="500",
     *         description="Returned on any other error"
     *     ),
     * @SWG\Parameter(
     *        name="JSON update body",
     *        in="body",
     *        description="json login request object",
     *        required=true,
     *        @SWG\Schema(
     *          type="array",
     *          @SWG\Items(
     *          )
     *        )
     *      )
     * @SWG\Tag(name="UserManagement")
     *
     * @Rest\Post("/register")
     */
    public function postRegisterAction(Request $request, UserPasswordEncoderInterface $passwordEncoder): Response
    {
        $user = new User();

        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->submit($request->request->all());

        if ($form->isSubmitted() && $form->isValid()) {
            // get Data from Form
            $user = $form->getData();

            //Check if Email and Nickname are not already used
            if ($this->userRepository->findOneCaseInsensitive(['email' => $form->get('email')->getData()])) {
                $view = $this->view(Error::withMessage('EMail exists already'), Response::HTTP_CONFLICT);

                return $this->handleView($view);
            }
            if ($this->userRepository->findOneCaseInsensitive(['nickname' => $form->get('nickname')->getData()])) {
                $view = $this->view(Error::withMessage('Nickname exists already'), Response::HTTP_CONFLICT);

                return $this->handleView($view);
            }

            // encode the plain password
            $user->setPassword(
                $passwordEncoder->encodePassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            // set defaults in User
            $user->setStatus(1);
            $user->setInfoMails(true);
            $user->setEmailConfirmed(false);

            $this->em->persist($user);
            $this->em->flush();

            // return the User Object

            $user = $this->userRepository->findOneBy(['email' => $user->getEMail()]);

            $view = $this->view($user, RESPONSE::HTTP_CREATED);
            $view->getContext()->setSerializeNull(true);
            $view->getContext()->addGroup('default');

            return $this->handleView($view);
        }

        $view = $this->view(Error::withMessageAndDetail('Invalid JSON Body supplied, please check the Documentation', $form->getErrors(true, false)), Response::HTTP_BAD_REQUEST);

        return $this->handleView($view);
    }

    /**
     * Checks if the User is allowed to Login.
     *
     * Checks Username/Password against the Database
     *
     * @SWG\Response(
     *     response=200,
     *     description="Returns UserObject"
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Returns if no EMail and/or Password could be found"
     * )
     * @SWG\Parameter(
     *     name="email",
     *     in="formData",
     *     type="string",
     *     description="EMail"
     * )
     * * @SWG\Parameter(
     *     name="password",
     *     in="formData",
     *     type="string",
     *     description="Plaintext Password"
     * )
     * @SWG\Tag(name="Authorization")
     *
     * @Rest\Post("/authorize")
     * @ParamConverter("login", converter="fos_rest.request_body")
     */
    public function postAuthorizeAction(Login $login, ConstraintViolationListInterface $validationErrors)
    {
        if (count($validationErrors) > 0) {
            $view = $this->view(Error::withMessageAndDetail("Invalid JSON Body supplied, please check the Documentation", $validationErrors[0]), Response::HTTP_BAD_REQUEST);
            return $this->handleView($view);
        }

        //Check if User can login
        $user = $this->loginService->checkCredentials($login->email, $login->password);

        if ($user) {
            $view = $this->view();
        } else {
            $view = $this->view(Error::withMessage('Invalid credentials'), Response::HTTP_NOT_FOUND);
        }

        return $this->handleView($view);
    }

    /**
     * Returns a Single Userobject.
     *
     * Supports searching via UUID and via E-Mail
     *
     * @Rest\Get("/{search}", requirements= {"search"="([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})|(\w+@\w+.\w+)"})
     */
    public function getUserAction(string $search)
    {
        if (preg_match('/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/', $search)) {
            // UUID based Search

            $user = $this->userRepository->findOneBy(['uuid' => $search]);
        } elseif (preg_match("/\w+@\w+.\w+/", $search)) {
            // E-Mail based Search

            $user = $this->userRepository->findOneBy(['email' => $search]);
        } else {
            $view = $this->view('', Response::HTTP_BAD_REQUEST);

            return $this->handleView($view);
        }

        if ($user) {
            $view = $this->view($user);
            $view->getContext()->setSerializeNull(true);
            $view->getContext()->addGroup('default');
        } else {
            $view = $this->view(Error::withMessage("User not found"), Response::HTTP_NOT_FOUND);
        }

        return $this->handleView($view);
    }

    /**
     * Edits a User.
     *
     * Edits a User
     * WARNING: for now it's mandatory to supply a full UserObject
     *
     * @Rest\Patch("/{uuid}", requirements= {"search"="[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}"})
     * @ParamConverter()
     */
    public function editUserAction(User $user, Request $request)
    {
        $form = $this->createForm(UserEditType::class, $user);

        // Specify clearMissing on false to support partial editing
        $form->submit($request->request->all(), false);
        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();

            $this->em->persist($user);
            $this->em->flush();

            $user = $this->userRepository->findOneBy(['uuid' => $user->getUuid()]);
            $view = $this->view($user);
            $view->getContext()->setSerializeNull(true);
            $view->getContext()->addGroup('default');

            return $this->handleView($view);
        } else {
            $view = $this->view(Error::withMessage("User not found"), Response::HTTP_NOT_FOUND);

            return $this->handleView($view);
        }
    }

    /**
     * Returns multiple Userobjects.
     *
     * Supports searching via UUID
     *
     * @Rest\Post("/search")
     * @ParamConverter("search", converter="fos_rest.request_body")
     */
    public function postUsersearchAction(Search $search, ConstraintViolationListInterface $validationErrors)
    {
        if (count($validationErrors) > 0) {
            $view = $this->view(Error::withMessageAndDetail("Invalid JSON Body supplied, please check the Documentation", $validationErrors[0]), Response::HTTP_BAD_REQUEST);
            return $this->handleView($view);
        }

        $user = $this->userRepository->findBySearch($search);

        $view = $this->view($user);
        $view->getContext()
            ->setSerializeNull(true)
            ->addGroup('default');
        return $this->handleView($view);
    }

    /**
     * Returns all Userobjects.
     *
     * @Rest\Get("")
     * @Rest\QueryParam(name="page", requirements="\d+", default="1")
     * @Rest\QueryParam(name="limit", requirements="\d+", default="10")
     * @Rest\QueryParam(name="q", default="")
     *
     */
    public function getUsersAction(Request $request, ParamFetcher $fetcher)
    {
        $page = intval($fetcher->get('page'));
        $limit = intval($fetcher->get('limit'));
        $filter = $fetcher->get('q');

        // Select all Users where the Status is greater then 0 (e.g. not disabled/locked/deactivated)
        $qb = $this->userRepository->findAllActiveQueryBuilder($filter);
        $pager = new Pagerfanta(new DoctrineORMAdapter($qb));
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
        $view->getContext()->setSerializeNull(true);
        $view->getContext()->addGroup('dto');
        $view->getContext()->addGroup('default');

        return $this->handleView($view);
    }

    /**
     * Checks availability of EMail and/or Nickname.
     *
     * @Rest\Post("/check")
     * @ParamConverter("userAvailability", converter="fos_rest.request_body")
     */
    public function checkAvailabilityAction(UserAvailability $userAvailability, ConstraintViolationListInterface $validationErrors)
    {
        if (count($validationErrors) > 0) {
            $view = $this->view(Error::withMessageAndDetail('Invalid JSON Body supplied, please check the Documentation', $validationErrors[0]), Response::HTTP_BAD_REQUEST);

            return $this->handleView($view);
        }

        if ('email' == $userAvailability->mode) {
            $user = $this->userRepository->findOneCaseInsensitive(['email' => $userAvailability->name]);
        } elseif ('nickname' == $userAvailability->mode) {
            $user = $this->userRepository->findOneCaseInsensitive(['nickname' => $userAvailability->name]);
        }

        if ($user) {
            $view = $this->view(null, Response::HTTP_NO_CONTENT);
        } else {
            $view = $this->view(null, Response::HTTP_NOT_FOUND);
        }

        return $this->handleView($view);
    }
}
