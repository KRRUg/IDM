<?php

namespace App\Controller\Rest;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\LoginService;
use App\Transfer\Error;
use App\Transfer\Login;
use App\Transfer\Register;
use App\Transfer\UserAvailability;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Swagger\Annotations as SWG;

/**
 * Class AuthController.
 *
 * @Rest\Route("/auth", name="auth_")
 */
class AuthController extends AbstractFOSRestController
{
    private LoginService $loginService;

    private UserRepository $userRepository;

    private EntityManagerInterface $em;

    /**
     * AuthController constructor.
     * @param LoginService $loginService
     * @param UserRepository $userRepository
     */
    public function __construct(EntityManagerInterface $em, LoginService $loginService, UserRepository $userRepository)
    {
        $this->em = $em;
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
     * @ParamConverter("register", converter="fos_rest.request_body", options={"deserializationContext": {"allow_extra_attributes": false}})
     */
    public function postRegisterAction(Register $register, ConstraintViolationListInterface $validationErrors, PasswordEncoderInterface $passwordEncoder): Response
    {
        if (count($validationErrors) > 0) {
            $view = $this->view(Error::withMessageAndDetail('Invalid JSON Body supplied, please check the Documentation', $validationErrors[0]), Response::HTTP_BAD_REQUEST);
            return $this->handleView($view);
        }

        $user = new User();

        if (!$this->userRepository->userAvailable($register->email, $register->nickname)) {
            $view = $this->view(Error::withMessage('EMail/Nickname already exists'), Response::HTTP_CONFLICT);
            return $this->handleView($view);
        }

        $user->setEmail($register->email);
        $user->setNickname($register->nickname);
        $user->setFirstname($register->firstname);
        $user->setSurname($register->surname);
        $user->setInfoMails($register->infoMail);

        // encode the plain password
        $user->setPassword($passwordEncoder->encodePassword($register->password, null));

        // set defaults in User
        $user->setStatus(1);
        $user->setEmailConfirmed(false);

        $this->em->persist($user);
        $this->em->flush();

        $view = $this->view($user, RESPONSE::HTTP_CREATED);
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
     * @SWG\Parameter(
     *     name="password",
     *     in="formData",
     *     type="string",
     *     description="Plaintext Password"
     * )
     * @SWG\Tag(name="Authorization")
     *
     * @Rest\Post("/authorize")
     * @ParamConverter("login", converter="fos_rest.request_body", options={"deserializationContext": {"allow_extra_attributes": false}})
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
     * Checks availability of EMail and/or Nickname.
     * TODO: is this required?
     *
     * @Rest\Post("/check")
     * @ParamConverter("userAvailability", converter="fos_rest.request_body", options={"deserializationContext": {"allow_extra_attributes": false}})
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
