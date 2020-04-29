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
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\Prefix;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Class ClanController.
 *
 * @Prefix("/clans")
 * @NamePrefix("rest_clans_")
 */
class ClanController extends AbstractFOSRestController
{
    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $em;
    /**
     * @var ClanRepository
     */
    private ClanRepository $clanRepository;
    /**
     * @var UserRepository
     */
    private UserRepository $userRepository;
    /**
     * @var UserClanRepository
     */
    private UserClanRepository $userClanRepository;

    public function __construct(EntityManagerInterface $entityManager, ClanRepository $clanRepository, UserRepository $userRepository, UserClanRepository $userClanRepository)
    {
        $this->em = $entityManager;
        $this->clanRepository = $clanRepository;
        $this->userRepository = $userRepository;
        $this->userClanRepository = $userClanRepository;
    }

    /**
     * Creates a Clan.
     *
     * @Rest\Post("")
     */
    public function createClanAction(Request $request)
    {
        $clan = new Clan();

        $form = $this->createForm(ClanCreateType::class, $clan);
        $form->submit($request->request->all());

        if ($form->isSubmitted() && $form->isValid()) {
            // get Data from Form
            $clan = $form->getData();

            //Check if ClanName and ClanTag are not already used
            if ($this->clanRepository->findOneByLowercase(['name' => $form->get('name')->getData()])) {
                $view = $this->view(Error::withMessage('ClanName exists already'), Response::HTTP_CONFLICT);

                return $this->handleView($view);
            }
            if ($this->clanRepository->findOneByLowercase(['clantag' => $form->get('clantag')->getData()])) {
                $view = $this->view(Error::withMessage('ClanTag exists already'), Response::HTTP_CONFLICT);

                return $this->handleView($view);
            }

            // encode the plain password
            $clan->setJoinPassword(password_hash($form->get('joinPassword')->getData(), PASSWORD_ARGON2ID));

            // add Creator as Admin and Member
            $user = $this->userRepository->findOneBy(['uuid' => $form->get('user')->getData()]);

            if ($user instanceof User) {
                $userclan = new UserClan();
                $userclan->setClan($clan);
                $userclan->setUser($user);
                $userclan->setAdmin(true);

                $this->em->persist($clan);
                $this->em->persist($userclan);
                $this->em->flush();
            } else {
                $view = $this->view(Error::withMessage('Supplied User could not be found'), Response::HTTP_BAD_REQUEST);

                return $this->handleView($view);
            }

            // return the Clan Object
            $clan = $this->clanRepository->findOneBy(['uuid' => $clan->getUuid()]);

            $view = $this->view($clan, Response::HTTP_CREATED);
            $view->getContext()->setSerializeNull(true);
            $view->getContext()->addGroup('default');

            return $this->handleView($view);
        }

        $view = $this->view(Error::withMessageAndDetail('Invalid JSON Body supplied, please check the Documentation', $form->getErrors(true, false)), Response::HTTP_BAD_REQUEST);

        return $this->handleView($view);
    }

    /**
     * Returns a single Clanobject.
     *
     * @Rest\Get("/{search}", requirements= {"search"="[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}"})
     *
     * @return Response
     */
    public function getClanAction(string $search)
    {
        $clan = $this->clanRepository->findOneWithActiveUsersByUuid($search);

        if ($clan) {
            $view = $this->view($clan);
            $view->getContext()->setSerializeNull(true);
            $view->getContext()->addGroup('clanview');
        } else {
            $view = $this->view(Error::withMessage('Clan not found'), Response::HTTP_NOT_FOUND);
        }

        return $this->handleView($view);
    }

    /**
     * Edits a Clan.
     *
     * Edits a Clan
     *
     * @Rest\Patch("/{uuid}", requirements= {"uuid"="[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}"})
     * @ParamConverter()
     */
    public function editClanAction(Clan $clan, Request $request)
    {
        $form = $this->createForm(ClanEditType::class, $clan);

        // Specify clearMissing on false to support partial editing
        $form->submit($request->request->all(), false);
        if ($form->isSubmitted() && $form->isValid()) {
            $formclan = $form->getData();

            // Check if the ClanName or Tag are already used
            $clantag = $this->clanRepository->findOneByLowercase(['clantag' => $form->get('clantag')->getData()]);
            if ($clantag->getUuid() !== $clan->getUuid()) {
                $view = $this->view(Error::withMessage('ClanTag already in use'), Response::HTTP_BAD_REQUEST);

                return $this->handleView($view);
            }
            $clanname = $this->clanRepository->findOneByLowercase(['clanname' => $form->get('name')->getData()]);
            if ($clanname->getUuid() !== $clan->getUuid()) {
                $view = $this->view(Error::withMessage('Clanname already in use'), Response::HTTP_BAD_REQUEST);

                return $this->handleView($view);
            }

            // Only set the Password when it's not empty
            if (null != $form->get('joinPassword')->getData() && '' != $form->get('joinPassword')->getData()) {
                $formclan->setJoinPassword(password_hash($form->get('joinPassword')->getData(), PASSWORD_ARGON2ID));
            }
            // Set Admins to the specified List
            if (is_array($form->get('admins')->getData()) && !empty($form->get('admins')->getData())) {
                // First remove all the Admins that are no longer in the List
                foreach ($this->userClanRepository->findAllAdminsByClanUuid($clan->getUuid()) as $admin) {
                    if (!array_key_exists((string) $admin->getUser()->getUuid(), $form->get('admins')->getData())) {
                        $admin->setAdmin(false);
                        $this->em->persist($admin);
                    }
                }
                // Add the new Admins
                foreach ($form->get('admins')->getData() as $admin) {
                    $clanuser = $this->userClanRepository->findOneClanUserByUuid($formclan->getUuid(), $admin);
                    if ($clanuser) {
                        if (true != $clanuser->getAdmin()) {
                            $clanuser->setAdmin(true);
                            $this->em->persist($clanuser);
                        }
                    } else {
                        $view = $this->view(Error::withMessageAndDetail('User UUID is not Member of the Clan', $admin), Response::HTTP_BAD_REQUEST);

                        return $this->handleView($view);
                    }
                }
            }
            $this->em->persist($clan);
            $this->em->flush();

            $formclan = $this->clanRepository->findOneBy(['uuid' => $formclan->getUuid()]);
            $view = $this->view($formclan);
            $view->getContext()->setSerializeNull(true);
            $view->getContext()->addGroup('clanview');

            return $this->handleView($view);
        } else {
            $view = $this->view(Error::withMessageAndDetail('Invalid JSON Body supplied, please check the Documentation', $form->getErrors(true, false)), Response::HTTP_BAD_REQUEST);

            return $this->handleView($view);
        }
    }

    /**
     * Delete a Clan.
     *
     * @Rest\Delete("/{uuid}", requirements= {"uuid"="[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}"})
     * @ParamConverter()
     */
    public function removeClanAction(Clan $clan)
    {

        $clanusers = $this->userClanRepository->findBy(['clan' => $clan]);

        if($clanusers) {
            foreach ($clanusers as $clanuser) {
                $this->em->remove($clanuser);
            }
        }

        $this->em->remove($clan);
        $this->em->flush();

        $view = $this->view(null, Response::HTTP_NO_CONTENT);

        return $this->handleView($view);
    }

    /**
     * Returns all Clanobjects.
     *
     * @Rest\Get("")
     */
    public function getClansAction(Request $request)
    {
        if ('list' == $request->query->get('select')) {
            // Get all Clans but without the User Relations
            $clans = $this->clanRepository->findAllWithoutUserRelations();
        } else {
            // Get all Clans
            $clans = $this->clanRepository->findAllWithActiveUsers();
        }

        if ($clans) {
            $view = $this->view($clans);
            $view->getContext()->setSerializeNull(true);
            $view->getContext()->addGroup('clanview');
        } else {
            $view = $this->view(Error::withMessage('No Clans found'), Response::HTTP_NOT_FOUND);
        }

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

        if ($users) {
            foreach ($users as $user) {
                if (true === $clanMemberRemove->strict && $admincount <= 1) {
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
            $view = $this->view(null, Response::HTTP_OK);
        } else {
            $view = $this->view(null, Response::HTTP_NOT_FOUND);
        }

        return $this->handleView($view);
    }
}
