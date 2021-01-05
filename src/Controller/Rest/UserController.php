<?php

namespace App\Controller\Rest;

use App\Entity\User;
use App\Form\UserEditType;
use App\Repository\UserRepository;
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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Class UserController.
 *
 * @Rest\Route("/users", name="rest_users_")
 */
class UserController extends AbstractFOSRestController
{
    private EntityManagerInterface $em;

    private UserRepository $userRepository;

    public function __construct(EntityManagerInterface $entityManager, UserRepository $userRepository)
    {
        $this->em = $entityManager;
        $this->userRepository = $userRepository;
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
     * WARNING: for now it's mandatory to supply a full UserObject
     * TODO: change this by adding a denormalizer
     *
     * @Rest\Patch("/{uuid}", requirements= {"uuid"="[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}"})
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
}
