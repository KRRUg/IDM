<?php

namespace App\Controller\Rest;

use App\Entity\Clan;
use App\Entity\User;
use App\Entity\UserClan;
use App\Form\ClanCreateType;
use App\Form\ClanEditType;
use App\Repository\ClanRepository;
use App\Repository\UserClanRepository;
use App\Repository\UserRepository;
use App\Transfer\ClanAvailability;
use App\Transfer\ClanMemberAdd;
use App\Transfer\ClanMemberRemove;
use App\Transfer\Error;
use App\Transfer\PaginationCollection;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcher;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Class ClanController.
 *
 * @Rest\Route("/clans", name="rest_clans_")
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

        $view = $this->view($new);
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
     * @Rest\Patch("/{uuid}/users", requirements= {"uuid"="[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}"})
     * @ParamConverter("clan", options={"mapping": {"uuid": "uuid"}})
     * @ParamConverter("clanMemberAdd", converter="fos_rest.request_body")
     */
    public function addMemberAction(Clan $clan, ClanMemberAdd $clanMemberAdd, ConstraintViolationListInterface $validationErrors)
    {
        if (count($validationErrors) > 0) {
            $view = $this->view(Error::withMessageAndDetail('Invalid JSON Body supplied, please check the Documentation', $validationErrors[0]), Response::HTTP_BAD_REQUEST);

            return $this->handleView($view);
        }

        if (null != $clanMemberAdd->joinPassword) {
            if (!password_verify($clanMemberAdd->joinPassword, $clan->getJoinPassword())) {
                $view = $this->view(Error::withMessage('Invalid Clan joinPassword'), Response::HTTP_FORBIDDEN);

                return $this->handleView($view);
            }
        }

        $users = $this->userRepository->findBy(['uuid' => $clanMemberAdd->users]);

        if (count($clanMemberAdd->users) != count($users)) {
            $actualusers = [];
            foreach ($users as $user) {
                $actualusers[] = $user->getUuid();
            }
            $missingusers = array_diff($clanMemberAdd->users, $actualusers);

            $view = $this->view(Error::withMessageAndDetail('Not all Users were found', implode(',', $missingusers)), Response::HTTP_BAD_REQUEST);

            return $this->handleView($view);
        }

        if ($users) {
            foreach ($users as $user) {
                if ($this->userClanRepository->findOneClanUserByUuid($clan->getUuid(), $user->getUuid())) {
                    $view = $this->view(Error::withMessageAndDetail('User is already a Member of the Clan', $user->getUuid()), Response::HTTP_BAD_REQUEST);

                    return $this->handleView($view);
                } else {
                    $clanuser = new UserClan();
                    $clanuser->setUser($user);
                    $clanuser->setClan($clan);

                    $this->em->persist($clanuser);
                }
            }

            $this->em->flush();

            $view = $this->view(null, Response::HTTP_NO_CONTENT);
        } else {
            $view = $this->view(Error::withMessage('No Users were found'), Response::HTTP_BAD_REQUEST);
        }

        return $this->handleView($view);
    }

    /**
     * Removes a User from a Clan.
     *
     * @Rest\Delete("/{uuid}/users", requirements= {"uuid"="[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}"})
     * @ParamConverter("clan", options={"mapping": {"uuid": "uuid"}})
     * @ParamConverter("clanMemberRemove", converter="fos_rest.request_body")
     */
    public function removeMemberAction(Clan $clan, ClanMemberRemove $clanMemberRemove, ConstraintViolationListInterface $validationErrors)
    {
        if (count($validationErrors) > 0) {
            $view = $this->view(Error::withMessageAndDetail('Invalid JSON Body supplied, please check the Documentation', $validationErrors[0]), Response::HTTP_BAD_REQUEST);

            return $this->handleView($view);
        }

        $users = $this->userRepository->findBy(['uuid' => $clanMemberRemove->users]);

        if (count($clanMemberRemove->users) != count($users)) {
            $actualusers = [];
            foreach ($users as $user) {
                $actualusers[] = $user->getUuid();
            }
            $missingusers = array_diff($clanMemberRemove->users, $actualusers);

            $view = $this->view(Error::withMessageAndDetail('Not all Users were found', implode(',', $missingusers)), Response::HTTP_BAD_REQUEST);

            return $this->handleView($view);
        }

        // TODO: Only fetch Count for Admins instead of the whole Objects -> faster!
        $admins = $this->userClanRepository->findAllAdminsByClanUuid($clan->getUuid());
        $admincount = count($admins);

        $adminarray = [];
        foreach ($admins as $admin) {
            $adminarray[] = $admin->getUser()->getUuid();
        }

        if ($users) {
            foreach ($users as $user) {
                if (true === $clanMemberRemove->strict && $admincount <= 1 && in_array($user->getUuid(), $adminarray)) {
                    // StrictMode for non-Admin Requests, so you cannot remove the last Owner
                    $view = $this->view(Error::withMessageAndDetail('You cannot remove the last Admin of the Clan', $user->getUuid()), Response::HTTP_BAD_REQUEST);

                    return $this->handleView($view);
                }

                $clanuser = $this->userClanRepository->findOneClanUserByUuid($clan->getUuid(), $user->getUuid());

                if ($clanuser) {
                    if (true === $clanuser->getAdmin()) {
                        --$admincount;
                    }
                    $this->em->remove($clanuser);
                } else {
                    $view = $this->view(Error::withMessageAndDetail('User is not a Member of the Clan', $user->getUuid()), Response::HTTP_BAD_REQUEST);

                    return $this->handleView($view);
                }
            }

            $this->em->flush();

            $view = $this->view(null, Response::HTTP_NO_CONTENT);
        } else {
            $view = $this->view(Error::withMessage('No Users were found'), Response::HTTP_BAD_REQUEST);
        }

        return $this->handleView($view);
    }

    /**
     * Checks availability of Clanname and/or Clantag.
     *
     * @Rest\Post("/check")
     * @ParamConverter("clanAvailability", converter="fos_rest.request_body")
     */
    public function checkAvailabilityAction(ClanAvailability $clanAvailability, ConstraintViolationListInterface $validationErrors)
    {
        if (count($validationErrors) > 0) {
            $view = $this->view(Error::withMessageAndDetail('Invalid JSON Body supplied, please check the Documentation', $validationErrors[0]), Response::HTTP_BAD_REQUEST);

            return $this->handleView($view);
        }

        if ('clantag' == $clanAvailability->mode) {
            $clan = $this->clanRepository->findOneByLowercase(['clantag' => $clanAvailability->name]);
        } elseif ('clanname' == $clanAvailability->mode) {
            $clan = $this->clanRepository->findOneByLowercase(['name' => $clanAvailability->name]);
        }

        if ($clan) {
            $view = $this->view(null, Response::HTTP_NO_CONTENT);
        } else {
            $view = $this->view(null, Response::HTTP_NOT_FOUND);
        }

        return $this->handleView($view);
    }
}
