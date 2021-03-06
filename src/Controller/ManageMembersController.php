<?php
/**
 * Created by PhpStorm.
 * UserForm: Ahmad Adlouni
 * Date: 1/25/2018
 * Time: 12:40 PM
 */

namespace App\Controller;


use App\Entity\Building;
use App\Entity\Device;
use App\Entity\Guard;
use App\Entity\NotificationSettings;
use App\Entity\Office;
use App\Entity\OfficeSettings;
use App\Entity\Role;
use App\Entity\Schedule;
use App\Entity\Token;
use App\Entity\User;
use App\Form\Type\AdminBuildingUpdateType;
use App\Form\Type\DeviceType;
use App\Form\Type\OfficeUserType;
use App\Form\Type\ScheduleType;
use App\Form\Type\UserType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Regex;

class ManageMembersController extends Controller
{

    /**
     * @Route("/manageMembers/addMember", name="addMember")
     * @param Session $session
     * @param Request $request
     * @return Request|Response
     */
    public function addMember(Session $session, Request $request)
    {
        if (!$session->has('gmail'))
            return $this->redirectToRoute('login');

        if (!in_array('fowner', $session->get('roles')) && !in_array('fadmin', $session->get('roles')))
            return $this->render('errors/access_denied.html.twig');

        $user = new User();
        $form = $this->createForm(UserType::class, $user);

        $form->handleRequest($request);
        if ($form->isSubmitted()) {

            if (!$form->isValid()) {

                $this->addFlash(
                    'danger',
                    'You have some errors. Please check below.'
                );
                return $this->render('manageMembers/addMember.html.twig', array(
                    'form' => $form->createView()
                ));
            }

            $em = $this->getDoctrine()->getManager();
            $role = $form['role']->getData();
            $building = $form['building']->getData();

            $user->addRole($role);
            $user->addBuilding($building);
            $user->setDateCreated(new \DateTime());

            if ($role->getRoleName() == 'sguard') {

                $em->persist($user);
                $em->flush();

                $guard = new Guard();
                $guard->setUser($user);
                $em->persist($guard);
                $em->flush();

                return $this->redirectToRoute('addSecurityGuard', array(
                    'userId' => $user->getId(),
                    'buildingId' => $building->getId()
                ));

            } else if ($role->getRoleName() == 'fadmin') {

                $building = $em->getRepository(Building::class)->find($form['building']->getData()->getId());

                if ($building->getAdmin() === null) {
                    $em->persist($user);
                    $em->flush();

                    $building->setAdmin($user);
                    $em->flush();
                } else {
                    $this->addFlash(
                        'danger',
                        $building->getName() . ' already has an Administrator!'
                    );

                    return $this->render('manageMembers/addMember.html.twig', array(
                        'form' => $form->createView()
                    ));
                }

            } else if ($role->getRoleName() == 'powner') {

                $em->persist($user);
                $em->flush();

                return $this->redirectToRoute('addPremiseOwner', array(
                    'userId' => $user->getId(),
                    'buildingId' => $building->getId()
                ));

            }

            $this->addFlash(
                'success',
                'Member added successfully!'
            );

            return $this->redirectToRoute('addMember');
        }

        return $this->render('manageMembers/addMember.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/manageMembers/addMember/addSecurityGuard/{userId}/{buildingId}", name="addSecurityGuard")
     * @param Request $request
     * @param $userId
     * @param $buildingId
     * @return Response
     */
    public function addSecurityGuard(Request $request, $userId, $buildingId)
    {
        if ($request->headers->get('referer')) {

            $user = $this->getDoctrine()->getRepository(User::class)->find($userId);
            $building = $this->getDoctrine()->getRepository(Building::class)->find($buildingId);

            $form = $this->createForm(ScheduleType::class, null, array(
                'building' => $building
            ));

            $form->handleRequest($request);
            if ($form->isSubmitted()) {

                if (!$form->isValid()) {
                    $this->addFlash(
                        'danger',
                        'You have some errors. Please check below.'
                    );
                    return $this->render('manageMembers/addSecurityGuard.html.twig', array(
                        'form' => $form->createView()
                    ));
                }

                $em = $this->getDoctrine()->getManager();

                $deviceMac = $form['device']->getData();
                if ($deviceMac === null) {
                    $this->addFlash(
                        'danger',
                        'Mac address should not be null.'
                    );
                    return $this->render('manageMembers/addSecurityGuard.html.twig', array(
                        'form' => $form->createView()
                    ));
                } else if (!$this->checkMacAddressRegex($deviceMac)) {
                    $this->addFlash(
                        'danger',
                        'MAC address must be consist of six groups of two hexadecimal digits, separated by colons :'
                    );
                    return $this->render('manageMembers/addSecurityGuard.html.twig', array(
                        'form' => $form->createView()
                    ));
                } else {
                    $isThereOne = $this->getDoctrine()->getRepository(Device::class)->findOneBy(['macAddress' => $deviceMac]);
                    if ($isThereOne) {
                        $this->addFlash(
                            'danger',
                            'MAC address already exists.'
                        );
                        return $this->render('manageMembers/addSecurityGuard.html.twig', array(
                            'form' => $form->createView()
                        ));
                    }
                }

                $device = new Device();
                $device->setDateCreated(new \DateTime());
                $device->setMacAddress($deviceMac);
                $em->persist($device);
                $em->flush();

                $guard = $this->getDoctrine()->getRepository(Guard::class)->findOneBy(['user' => $user]);
                $guard->setDevice($device);
                $em->flush();

                $shifts = $form->get('shift')->getData();
                $gate = $form->get('gate')->getData();
                foreach ($shifts as $shift) {
                    $schedule = new Schedule();
                    $schedule->setGuard($guard);
                    $schedule->setGate($gate);
                    $schedule->setShift($shift);
                    $em->persist($schedule);
                    $em->flush();
                }

                $this->addFlash(
                    'success',
                    'Member added successfully!'
                );

                return $this->redirectToRoute('addMember');
            }

            return $this->render('manageMembers/addSecurityGuard.html.twig', array(
                'form' => $form->createView()
            ));
        }

        return $this->render('errors/access_denied.html.twig');
    }

    private function checkMacAddressRegex($macAddress)
    {
        return (preg_match('/^([0-9A-Fa-f]{2}[:]){5}([0-9A-Fa-f]{2})$/', $macAddress) == 1);
    }

    /**
     * @Route("/manageMembers/addMember/addPremiseOwner/{userId}/{buildingId}", name="addPremiseOwner")
     * @param Request $request
     * @param $userId
     * @param $buildingId
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function addPremiseOwner(Request $request, $userId, $buildingId)
    {
        if ($request->headers->get('referer')) {

            $user = $this->getDoctrine()->getRepository(User::class)->find($userId);
            $building = $this->getDoctrine()->getRepository(Building::class)->find($buildingId);

            $form = $this->createForm(OfficeUserType::class, null, array(
                'building' => $building
            ));

            $form->handleRequest($request);
            if ($form->isSubmitted()) {

                if (!$form->isValid()) {
                    $this->addFlash(
                        'danger',
                        'You have some errors. Please check below.'
                    );
                    return $this->render('manageMembers/addPremiseOwner.html.twig', array(
                        'form' => $form->createView()
                    ));
                }

                $em = $this->getDoctrine()->getManager();
                $office = $form->get('office')->getData();

                $officeUpdate = $em->getRepository(Office::class)->find($office->getId());
                $officeUpdate->setUser($user);
                $em->flush();

                $this->addFlash(
                    'success',
                    'Member added successfully!'
                );

                return $this->redirectToRoute('addMember');
            }

            return $this->render('manageMembers/addPremiseOwner.html.twig', array(
                'form' => $form->createView()
            ));
        }
        return $this->render('errors/access_denied.html.twig');
    }

    /**
     * @Route("/member/{userId}", name="viewProfile", requirements={"userId"="\d+"})
     * @param Session $session
     * @param $userId
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function viewProfile(Session $session, $userId)
    {
        if (!$session->has('gmail'))
            return $this->redirectToRoute('login');


        if ($session->get('user')->getId() != $userId) {
            if (!in_array('fowner', $session->get('roles')) && !in_array('fadmin', $session->get('roles')))
                return $this->render('errors/access_denied.html.twig');
        }

        $user = $this->getDoctrine()->getRepository(User::class)->find($userId);

        if ($user) {

            $userDetails = array();

            if (in_array('Security Guard', $this->getUserRoles($user))) {
                $guard = $this->getDoctrine()->getRepository(Guard::class)->findOneBy(['user' => $user]);
                $userDetails['guard'] = $guard;
            }

            if (in_array('Facility Administrator', $this->getUserRoles($user))) {
                $buildings = $this->getDoctrine()->getRepository(Building::class)->findOneBy(['admin' => $user]);
                $userDetails['buildings'] = $buildings;
            }

            if (in_array('Premise Owner', $this->getUserRoles($user))) {
                $office = $this->getDoctrine()->getRepository(Office::class)->findOneBy(['user' => $user]);
                $userDetails['offices'] = $office === null ? null : $office;
            }

            $buildingIn = $this->getUserBuildings($user);

            /**
             * @var Building $buildings
             * @var Guard $guard
             * @var Office $offices
             */
            return $this->render('manageMembers/viewUserProfile.html.twig', array(
                'user' => $user,
                'roles' => $this->getUserRoles($user),
                'userDetails' => $userDetails,
                'buildingIn' => $buildingIn
            ));
        }

        return $this->render('errors/not_found.html.twig');
    }

    /**
     * @Route("/member/{userId}/edit", name="editProfile")
     * @param Request $request
     * @param Session $session
     * @param $userId
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editProfile(Request $request, Session $session, $userId)
    {
        if (!$session->has('gmail'))
            return $this->redirectToRoute('login');

        if (!in_array('fowner', $session->get('roles')))
            return $this->render('errors/access_denied.html.twig');

        $entityManager = $this->getDoctrine()->getManager();
        $user = $entityManager->getRepository(User::class)->find($userId);

        if (!$user)
            return $this->render('errors/not_found.html.twig');

        // Create the form for the personal information
        $personalInfoForm = $this->createForm(UserType::class, $user, array(
            'action' => $this->generateUrl('editProfile', ['_fragment' => 'personal_info', 'userId' => $userId])
        ));
        $personalInfoForm->remove('role');
        $personalInfoForm->remove('building');
        $personalInfoForm->handleRequest($request);

        // Create the form for the building administrator info
        if (in_array('Facility Administrator', $this->getUserRoles($user))) {
            $buildingAdministratorForm = $this->createForm(AdminBuildingUpdateType::class, null, array(
                'action' => $this->generateUrl('editProfile', ['_fragment' => 'admin_info', 'userId' => $userId])
            ));
            $buildingAdministratorForm->handleRequest($request);
        } else {
            $buildingAdministratorForm = null;
        }

        // Create the form for the device info (Security Guard - MAC Address)
        if (in_array('Security Guard', $this->getUserRoles($user))) {
            $guard = $entityManager->getRepository(Guard::class)->findOneBy(['user' => $user]);
            $device = $guard->getDevice();

            if (!$device)
                $device = new Device();

            $deviceForm = $this->createForm(DeviceType::class, $device, array(
                'action' => $this->generateUrl('editProfile', ['_fragment' => 'device_info', 'userId' => $userId])
            ));

            $scheduleForm = $this->createForm(ScheduleType::class, null, array(
                'action' => $this->generateUrl('editProfile', ['_fragment' => 'schedule_info', 'userId' => $userId]),
                'building' => $user->getBuildings()->get(0)
            ));
            $scheduleForm->remove('device');

            $scheduleForm->handleRequest($request);
        } else {
            $deviceForm = null;
            $scheduleForm = null;
        }

        // Create the form for the office info (Premise Owner)
        if (in_array('Premise Owner', $this->getUserRoles($user))) {
            $buildings = $user->getBuildings();
            $building = $buildings->get(0);
            $officeForm = $this->createForm(OfficeUserType::class, null, array(
                'building' => $building,
                'action' => $this->generateUrl('editProfile', ['_fragment' => 'office_info', 'userId' => $userId])
            ));
            $officeForm->handleRequest($request);
        } else {
            $officeForm = null;
        }

        // Block that checks the submission of the personal info form
        if ($personalInfoForm->isSubmitted()) {
            $fragment = 'personal_info';
            if (!$personalInfoForm->isValid()) {
                $this->addFlash(
                    'danger',
                    'You have some errors. Please check below.'
                );
                return $this->renderEditProfilePage($user, $personalInfoForm, $deviceForm, $officeForm, $buildingAdministratorForm, $scheduleForm, $fragment);
            }
            $entityManager->flush();
            $this->addFlash(
                'success',
                'User personal info updated successfully!'
            );
            return $this->renderEditProfilePage($user, $personalInfoForm, $deviceForm, $officeForm, $buildingAdministratorForm, $scheduleForm, $fragment);
        }

        // Block that checks the submission of the building info form (admin)
        if ($buildingAdministratorForm !== null) {
            if ($buildingAdministratorForm->isSubmitted()) {
                $fragment = 'admin_info';
                if (!$buildingAdministratorForm->isValid()) {
                    $this->addFlash(
                        'danger',
                        'You have some errors. Please check below.'
                    );
                    return $this->renderEditProfilePage($user, $personalInfoForm, $deviceForm, $officeForm, $buildingAdministratorForm, $scheduleForm, $fragment);
                }
                $building = $buildingAdministratorForm->get('building')->getData();
                $building->setAdmin($user);
                $entityManager->flush();
                $this->addFlash(
                    'success',
                    'Administrator info updated successfully!'
                );
                return $this->renderEditProfilePage($user, $personalInfoForm, $deviceForm, $officeForm, $buildingAdministratorForm, $scheduleForm, $fragment);
            }
        }

        // Block that checks the submission of the office info form
        if ($officeForm !== null) {
            if ($officeForm->isSubmitted()) {
                $fragment = 'office_info';
                if (!$officeForm->isValid()) {
                    $this->addFlash(
                        'danger',
                        'You have some errors. Please check below.'
                    );
                    return $this->renderEditProfilePage($user, $personalInfoForm, $deviceForm, $officeForm, $buildingAdministratorForm, $scheduleForm, $fragment);
                }

                $oldOffice = $entityManager->getRepository(Office::class)->findOneBy(['user' => $user]);
                if ($oldOffice) {
                    $oldOffice->removeUser();
                    $entityManager->flush();
                }

                $newOffice = $officeForm->get('office')->getData();
                $newOffice->setUser($user);
                $entityManager->flush();

                $this->addFlash(
                    'success',
                    'Office info updated successfully!'
                );
                return $this->renderEditProfilePage($user, $personalInfoForm, $deviceForm, $officeForm, $buildingAdministratorForm, $scheduleForm, $fragment);
            }
        }

        // Block that checks the submission of the schedule form
        if ($scheduleForm !== null) {
            if ($scheduleForm->isSubmitted()) {
                $fragment = 'schedule_info';
                if (!$scheduleForm->isValid()) {
                    $this->addFlash(
                        'danger',
                        'You have some errors. Please check below.'
                    );

                    return $this->renderEditProfilePage($user, $personalInfoForm, $deviceForm, $officeForm, $buildingAdministratorForm, $scheduleForm, $fragment);
                }

                $guard = $entityManager->getRepository(Guard::class)->findOneBy(['user' => $user]);
                $schedules = $entityManager->getRepository(Schedule::class)->findBy(['guard' => $guard]);
                foreach ($schedules as $schedule)
                    $entityManager->remove($schedule);

                $shifts = $scheduleForm->get('shift')->getData();
                $gate = $scheduleForm->get('gate')->getData();
                foreach ($shifts as $shift) {
                    $schedule = new Schedule();
                    $schedule->setGuard($guard);
                    $schedule->setGate($gate);
                    $schedule->setShift($shift);
                    $entityManager->persist($schedule);
                    $entityManager->flush();
                }

                $this->addFlash(
                    'success',
                    'Schedule updated successfully!'
                );
                return $this->renderEditProfilePage($user, $personalInfoForm, $deviceForm, $officeForm, $buildingAdministratorForm, $scheduleForm, $fragment);
            }
        }

        // Block that checks the submission of the device form
        if ($deviceForm !== null) {
            if ($deviceForm->isSubmitted()) {
                $fragment = 'device_info';
                if (!$deviceForm->isValid()) {
                    $this->addFlash(
                        'danger',
                        'You have some errors. Please check below.'
                    );
                    return $this->renderEditProfilePage($user, $personalInfoForm, $deviceForm, $officeForm, $buildingAdministratorForm, $scheduleForm, $fragment);
                }

                $device = $deviceForm->getData();
                $device->setDateCreated(new \DateTime());
                $entityManager->persist($device);
                $entityManager->flush();

                $guard = $entityManager->getRepository(Guard::class)->findOneBy(['user' => $user]);
                $guard->setDevice($device);
                $entityManager->flush();

                $this->addFlash(
                    'success',
                    'Device info updated successfully!'
                );

                return $this->renderEditProfilePage($user, $personalInfoForm, $deviceForm, $officeForm, $buildingAdministratorForm, $scheduleForm, $fragment);
            }
        }

        return $this->renderEditProfilePage($user, $personalInfoForm, $deviceForm, $officeForm, $buildingAdministratorForm, $scheduleForm);
    }

    /**
     * @param User $user
     * @param $personalInfoForm
     * @param $deviceForm
     * @param $officeForm
     * @param $buildingAdministratorForm
     * @param $schedulesForm
     * @param $fragment
     * @return Response
     */
    private function renderEditProfilePage(User $user, $personalInfoForm, $deviceForm, $officeForm, $buildingAdministratorForm, $schedulesForm, $fragment = null)
    {
        return $this->render('manageMembers/editUserProfile.twig', array(
            'user' => $user,
            'roles' => $this->getUserRoles($user),
            'personalInfoForm' => $personalInfoForm->createView(),
            'deviceForm' => $deviceForm !== null ? $deviceForm->createView() : null,
            'officeForm' => $officeForm !== null ? $officeForm->createView() : null,
            'buildingAdministratorForm' => $buildingAdministratorForm !== null ? $buildingAdministratorForm->createView() : null,
            'schedulesForm' => $schedulesForm !== null ? $schedulesForm->createView() : null,
            'fragment' => $fragment
        ));
    }

    /**
     * @Route("/api/getGuardSchedule/{guardId}", methods={"GET", "POST"})
     * @param $guardId
     * @return JsonResponse
     */
    public function getGuardSchedule($guardId)
    {
        $guard = $this->getDoctrine()->getRepository(Guard::class)->find($guardId);
        if (!$guard)
            die();

        $schedules = $this->getDoctrine()->getRepository(Schedule::class)->findBy(['guard' => $guard]);
        $scheduleArray = array();
        $day = date('w');
        $week_end = date('d', strtotime('+' . (6 - $day) . ' days'));

        foreach ($schedules as $schedule) {
            $scheduleInfo = array();
            $scheduleInfo['title'] = $schedule->getGate()->getName();

            $dateFromShiftDay = date('d', strtotime($schedule->getShift()->getDay()));
            if ($dateFromShiftDay > $week_end)
                $dateFromShiftDay = strtotime('-7 days', strtotime($schedule->getShift()->getDay()));
            else
                $dateFromShiftDay = strtotime($schedule->getShift()->getDay());

            $dayLetter = date('D', $dateFromShiftDay);
            $dayNumber = date('d', $dateFromShiftDay);
            $month = date('M', $dateFromShiftDay);
            $year = date('Y', $dateFromShiftDay);

            $startDate = $dayLetter . ' ' . $month . ' ' . $dayNumber . ' ' . $year . ' ' . date_format($schedule->getShift()->getStartTime(), 'H:i:s') . ' GMT0000';
            $endDate = $dayLetter . ' ' . $month . ' ' . $dayNumber . ' ' . $year . ' ' . date_format($schedule->getShift()->getEndTime(), 'H:i:s') . ' GMT0000';

            $scheduleInfo['start'] = $startDate;
            $scheduleInfo['end'] = $endDate;
            $scheduleInfo['allDay'] = false;

            $scheduleArray[] = $scheduleInfo;
        }

        return $this->json($scheduleArray);
    }

    /**
     * @Route("/member/{userId}/edit/delete", name="deleteAccount")
     * @param $userId
     * @return JsonResponse
     */
    public function deleteAccount($userId)
    {
        $error = array('success' => 'no');
        $success = array('success' => 'yes');

        $entityManager = $this->getDoctrine()->getManager();
        $user = $entityManager->getRepository(User::class)->find($userId);

        if (!$user)
            return new JsonResponse($error);

        if (in_array('Facility Administrator', $this->getUserRoles($user))) {
            $buildings = $entityManager->getRepository(Building::class)->findBy(['admin' => $user]);
            if ($buildings) {
                foreach ($buildings as $building) {
                    $building->removeAdmin();
                    $entityManager->flush();
                }
            }
        }

        if (in_array('Premise Owner', $this->getUserRoles($user))) {
            $offices = $entityManager->getRepository(Office::class)->findBy(['user' => $user]);
            if ($offices) {
                foreach ($offices as $office) {

                    $officeSettings = $entityManager->getRepository(OfficeSettings::class)->findOneBy(['office' => $office]);
                    if ($officeSettings) {
                        $officeSettings->initializeSettings();
                        $entityManager->flush();
                    }

                    $office->removeUser();
                    $entityManager->flush();
                }
            }
        }

        if (in_array('Security Guard', $this->getUserRoles($user))) {
            $guard = $entityManager->getRepository(Guard::class)->findOneBy(['user' => $user]);
            $schedules = $entityManager->getRepository(Schedule::class)->findBy(['guard' => $guard]);
            if ($schedules) {
                foreach ($schedules as $schedule) {
                    $entityManager->remove($schedule);
                    $entityManager->flush();
                }
            }

            $device = $guard->getDevice();
            if ($device !== null) {
                $guard->setDevice(null);
                $entityManager->flush();

                $getDevice = $entityManager->getRepository(Device::class)->find($device);
                if ($getDevice) {
                    $entityManager->remove($getDevice);
                    $entityManager->flush();
                }
            }

            $entityManager->remove($guard);
            $entityManager->flush();
        }

        $token = $entityManager->getRepository(Token::class)->findOneBy(['user' => $user]);
        if ($token) {
            $entityManager->remove($token);
            $entityManager->flush();
        }

        $notificationSettings = $entityManager->getRepository(NotificationSettings::class)->findOneBy(['user' => $user]);
        if ($notificationSettings) {
            $entityManager->remove($notificationSettings);
            $entityManager->flush();
        }

        $entityManager->remove($user);
        $entityManager->flush();

        $user = $entityManager->getRepository(User::class)->find($userId);

        if ($user)
            return new JsonResponse($error);
        return new JsonResponse($success);
    }

    /**
     * @Route("/manageMembers/viewMembers", name="viewUsers")
     * @param Session $session
     * @return Response
     */
    public function viewUsers(Session $session)
    {
        if (!$session->has('gmail'))
            return $this->redirectToRoute('login');

        if (!in_array('fowner', $session->get('roles')) && !in_array('fadmin', $session->get('roles')))
            return $this->render('errors/access_denied.html.twig');

        return $this->render('manageMembers/viewUsers.html.twig');
    }

    /**
     * @Route("/manageMembers/search", name="userAdvancedSearch")
     * @param Session $session
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function advancedSearch(Session $session)
    {
        if (!$session->has('gmail'))
            return $this->redirectToRoute('login');

        if (!in_array('fowner', $session->get('roles')) && !in_array('fadmin', $session->get('roles')))
            return $this->render('errors/access_denied.html.twig');

        $rolesOptions = '';
        $roles = $this->getDoctrine()->getRepository(Role::class)->findAll();
        foreach ($roles as $role) {
            if ($role->getRoleName() === 'fowner' || $role->getRoleName() === 'fadmin')
                continue;
            $rolesOptions .= '<option value="' . $role->getId() . '">' . $this->getRoleName($role) . '</option>';
        }

        if (in_array('fowner', $session->get('roles'))) {
            $buildings = $this->getDoctrine()->getRepository(Building::class)->findAll();
        } else {
            $buildings = $this->getDoctrine()->getRepository(Building::class)->findBy(['admin' => $session->get('user')]);
        }

        return $this->render('manageMembers/searchMembers.html.twig', array(
            'buildings' => $buildings,
            'roles' => $rolesOptions,
        ));
    }

    /**
     * @Route("/api/members/search", name="searchMembers", methods={"GET"})
     * @param Request $request
     * @return JsonResponse
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getUsers(Request $request)
    {
        $member_id = $request->query->get('member_id');
        $first_name = $request->query->get('member_first_name');
        $last_name = $request->query->get('member_last_name');
        $phone_number = $request->query->get('member_phone_number');
        $gmail = $request->query->get('member_gmail');
        $member_role = $request->query->get('member_role');
        $buildingId = $request->query->get('member_building');
        $dateCreated = $request->query->get('member_date_created');

        if ($buildingId !== null)
            $building = $this->getDoctrine()->getRepository(Building::class)->find($buildingId);

        if ($member_role !== null)
            $role = $this->getDoctrine()->getRepository(Role::class)->find($member_role);

        $users = $this->getDoctrine()->getRepository(User::class)->advancedSearch($member_id, $first_name, $last_name,
            $gmail, $phone_number, $member_role, $buildingId, $dateCreated);

        $iTotalRecords = count($users);
        $iDisplayLength = intval($request->query->get('length'));
        $iDisplayLength = $iDisplayLength < 0 ? $iTotalRecords : $iDisplayLength;
        $iDisplayStart = intval($request->query->get('start'));
        $sEcho = intval($request->query->get('draw'));

        $records = array();
        $records['data'] = array();

        $end = $iDisplayStart + $iDisplayLength;
        $end = $end > $iTotalRecords ? $iTotalRecords : $end;

        for ($i = $iDisplayStart; $i < $end; $i++) {
            $id = ($i + 1);
            $records['data'][] = array(
                '<input type="checkbox" name="id[' . $users[$i]['id'] . ']" value="' . $id . '">',
                $users[$i]['id'],
                $users[$i]['date_created'],
                $users[$i]['given_name'],
                $users[$i]['family_name'],
                $users[$i]['gmail'],
                '+961 ' . $users[$i]['phone_nb'],
                $this->getRoleName($role),
                $building->getName(),
                '<a href="' . $this->generateUrl('viewProfile', ['userId' => $users[$i]['id']]) . '" class="btn btn-sm btn-outline grey-salsa"><i class="fa fa-search"></i> View</a>',
            );
        }

        $records['draw'] = $sEcho;
        $records['recordsTotal'] = $iTotalRecords;
        $records['recordsFiltered'] = $iTotalRecords;

        return new JsonResponse($records);
    }

    /**
     * @param Session $session
     * @param int $page
     * @param string $query
     * @return JsonResponse
     * @Route("/api/getAllUsers/{page}", name="getAllUsers", methods={"GET"})
     * @Route("/api/getAllUsers/{page}/{query}", methods={"GET"})
     */
    public function getAllUsers(Session $session, $page = 1, $query = '')
    {
        $currentPage = $page;

        if (in_array('fowner', $session->get('roles')))
            $building = null;
        else
            $building = $this->getDoctrine()->getRepository(Building::class)->findOneBy(['admin' => $session->get('user')]);

        $repo = $this->getDoctrine()->getRepository(User::class);
        $users = $repo->getAllUsers($currentPage, $query, $building);

        $totalUsersReturned = $users->getIterator()->count();
        $totalUsers = $users->count();
        $limit = 10;
        $maxPages = ceil($totalUsers / $limit);

        $data = array();
        $data['totalUsers'] = $totalUsers;
        $data['totalUsersReturned'] = $totalUsersReturned;
        $data['limit'] = $limit;
        $data['currentPage'] = (int)$currentPage;
        $data['maxPages'] = $maxPages;

        $usersArray = array();

        foreach ($users as $user) {
            $userInfo = array();
            $userInfo['id'] = $user->getId();
            $userInfo['givenName'] = $user->getGivenName();
            $userInfo['familyName'] = $user->getFamilyName();
            $userInfo['gmail'] = $user->getGmail();
            $userInfo['dob'] = date_format($user->getDob(), 'jS F, Y');
            $userInfo['role'] = implode(',<br>', $this->getUserRoles($user));
            $userInfo['building'] = implode(',<br>', $this->getUserBuildings($user));
            $userInfo['dateCreated'] = date_format($user->getDateCreated(), 'jS F, Y, g:i a');

            $usersArray[] = $userInfo;
        }
        $data['users'] = $usersArray;

        return new JsonResponse($data);
    }

    /**
     * @param User $user
     * @return array
     */
    private function getUserRoles(User $user): array
    {
        $roles = $user->getRoles();
        $rolesArray = array();
        foreach ($roles as $value) {
            $rolesArray[] = $this->getRoleName($value);
        }

        return $rolesArray;
    }

    /**
     * @param User $user
     * @return array
     */
    private function getUserBuildings(User $user): array
    {
        $buildings = $user->getBuildings();
        $buildingsArray = array();
        foreach ($buildings as $building)
            $buildingsArray[] = $building->getName();

        return $buildingsArray;
    }

    /**
     * @param $role
     * @return string
     */
    private function getRoleName($role): string
    {
        if ($role->getRoleName() == 'fowner')
            return 'Facility Owner';
        else if ($role->getRoleName() == 'fadmin')
            return 'Facility Administrator';
        else if ($role->getRoleName() == 'powner')
            return 'Premise Owner';
        else
            return 'Security Guard';
    }
}