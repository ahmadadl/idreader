<?php
/**
 * Created by PhpStorm.
 * User: Ahmad Adlouni
 * Date: 4/26/2018
 * Time: 5:49 PM
 */

namespace App\Controller\Api;


use App\Entity\Appointment;
use App\Entity\NotificationSettings;
use App\Entity\Office;
use App\Entity\Token;
use App\EntityClass\Firebase;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route as FRoute;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\EntityClass\Notification;

/**
 * Class AppointmentRestController
 * @package App\Controller\Api
 * @FRoute("/api/appointments")
 */
class AppointmentRestController extends Controller
{

    /**
     * @Route("/check", methods={"POST"})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function checkIfAppointment(Request $request)
    {
        if ($request->request->get('ssn') === null || $request->request->get('from_userId') === null)
            return $this->json(array(
                'success' => false,
                'message' => 'Not all variables are defined.'
            ), Response::HTTP_NOT_FOUND);

        $ssn = $request->request->get('ssn');
        $from = $request->request->get('from_userId');

        try {
            $appointment = $this->getDoctrine()->getRepository(Appointment::class)->findBySsnAndTodayDay($ssn);
            $officeName = $appointment->getOffice();

            $office = $this->getDoctrine()->getRepository(Office::class)->findOneBy(['officeNb' => $officeName]);
            $premiseOwner = $office->getUser();
            $notificationSettings = $this->getDoctrine()->getRepository(NotificationSettings::class)->findOneBy(['user' => $premiseOwner]);

            if (!$notificationSettings->isEnabled())
                return $this->json(array(
                    'success' => true,
                    'hasAppointment' => true,
                    'messageSent' => false,
                    'inTime' => true,
                    'message' => 'Notifications are disabled.',
                    'office' => $office->getId()
                ), Response::HTTP_OK);


            $timeNow = new \DateTime();

            $timeOfAppointmentString = $appointment->getTime()->format('H:i');
            $timeNowString = $timeNow->format('H:i');

            $timeOfAppointmentArray = explode(':', $timeOfAppointmentString);
            $timeNowArray = explode(':', $timeNowString);

            $timeAppointmentInMin = (((int)$timeOfAppointmentArray[0]) * 60) + (int)$timeOfAppointmentArray[1];
            $timeNowInMin = (((int)$timeNowArray[0]) * 60) + (int)$timeNowArray[1];

            $interval = $timeNowInMin - $timeAppointmentInMin;
            $shouldSendAppointment = $interval > $notificationSettings->getLateAfter() ? true : false;

            if ($shouldSendAppointment) {

                $premiseOwnerToken = $this->getDoctrine()->getRepository(Token::class)->findOneBy(['user' => $premiseOwner])->getToken();

                $title = 'ID Card Reader';
                $message = 'Allow ' . $appointment->getApplicantName() . ' to enter? He is ' . $interval . ' minutes late.';

                $notificationBuilder = new Notification($title, $message, $premiseOwnerToken, $this->getParameter('firebase_server_key'), $from);
                $notification = $notificationBuilder->build();
                $headers = $notificationBuilder->getHeader();

                $firebase = new Firebase($this->getParameter('firebase_url'), $headers, $notification);
                if ($result = $firebase->sendNotification() === true)
                    return $this->json(array(
                        'success' => true,
                        'hasAppointment' => true,
                        'messageSent' => true,
                        'inTime' => false,
                        'message' => 'Notification sent successfully.',
                        'office' => $office->getId()
                    ));

                return $this->json(array(
                    'success' => false,
                    'hasAppointment' => true,
                    'messageSent' => false,
                    'inTime' => false,
                    'message' => 'Error in sending notification. Please try again.',
                    'office' => $office->getId()
                ));
            }

            return $this->json(array(
                'success' => true,
                'hasAppointment' => true,
                'messageSent' => false,
                'inTime' => true,
                'message' => 'Can access.',
                'office' => $office->getId()
            ));

        } catch (NoResultException | NonUniqueResultException $e) {

            return $this->json(array(
                'success' => true,
                'hasAppointment' => false,
                'message' => 'Visitor has no appointment.'
            ));
        }
    }

}