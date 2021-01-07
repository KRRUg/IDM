<?php

namespace App\Controller\Rest;

use App\Repository\UserRepository;
use App\Service\LoginService;
use App\Transfer\Error;
use App\Transfer\Login;
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
    private PasswordEncoderInterface $passwordEncoder;

    /**
     * AuthController constructor.
     * @param LoginService $loginService
     * @param UserRepository $userRepository
     */
    public function __construct(EntityManagerInterface $em, LoginService $loginService, UserRepository $userRepository, PasswordEncoderInterface $passwordEncoder)
    {
        $this->em = $em;
        $this->loginService = $loginService;
        $this->userRepository = $userRepository;
        $this->passwordEncoder = $passwordEncoder;
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

}
