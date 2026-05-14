<?php

namespace App\Controller\Patient;

use App\Entity\Appointment;
use App\Entity\Creneau;
use App\Entity\User;
use App\Repository\AppointmentRepository;
use App\Repository\CreneauRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/patient/calendar')]
#[IsGranted('ROLE_PATIENT')]
final class PatientCalendarController extends AbstractController
{
    #[Route('/', name: 'app_patient_calendar', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('patient/calendar/index.html.twig');
    }

    #[Route('/events', name: 'app_patient_calendar_events', methods: ['GET'])]
    public function events(CreneauRepository $creneaux, AppointmentRepository $appointments): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'User not found'], 403);
        }

        $myCreneaux = $creneaux->createQueryBuilder('c')
            ->where('c.patient = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        $events = [];
        foreach ($myCreneaux as $creneau) {
            /** @var Creneau $creneau */
            $psy = null;
            $dispo = $creneau->getDisponibilite();
            if ($dispo && $dispo->getCabinet()) {
                $psyCabinet = $dispo->getCabinet()->getPsyCabinets()->first();
                if ($psyCabinet) {
                    $psy = $psyCabinet->getPsychologue();
                }
            }

            $status = $creneau->getStatut();
            if ($psy) {
                $dayMap = [
                    'MONDAY' => 'MONDAY', 'TUESDAY' => 'TUESDAY', 'WEDNESDAY' => 'WEDNESDAY',
                    'THURSDAY' => 'THURSDAY', 'FRIDAY' => 'FRIDAY', 'SATURDAY' => 'SATURDAY', 'SUNDAY' => 'SUNDAY'
                ];
                $date = $creneau->getDateCreneau();
                $heure = $creneau->getHeure();
                $dayOfWeek = $dayMap[strtoupper($date->format('l'))] ?? 'MONDAY';
                $period = ((int)$heure->format('H') < 18) ? 'DAY' : 'NIGHT';

                $appointment = $appointments->createQueryBuilder('a')
                    ->join('a.plan', 'p')
                    ->where('a.patient = :patient')
                    ->andWhere('p.psychologue = :psy')
                    ->andWhere('p.dayOfWeek = :day')
                    ->andWhere('p.period = :period')
                    ->setParameter('patient', $user)
                    ->setParameter('psy', $psy)
                    ->setParameter('day', $dayOfWeek)
                    ->setParameter('period', $period)
                    ->orderBy('a.id', 'DESC')
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getOneOrNullResult();

                if ($appointment) {
                    $status = $appointment->getStatus();
                }
            }

            $title = $psy ? 'Dr. ' . $psy->getPrenom() . ' ' . $psy->getNom() : 'Rendez-vous';
            $start = $creneau->getDateCreneau()->format('Y-m-d') . 'T' . $creneau->getHeure()->format('H:i:s');
            
            $color = '#5B9BD5'; 
            if ($status === Appointment::STATUS_PAID) {
                $color = '#2ecc71'; 
            } elseif ($status === Appointment::STATUS_CONFIRMED) {
                $color = '#3498db'; 
            } elseif ($status === Creneau::STATUT_ANNULE || $status === Appointment::STATUS_CANCELLED) {
                $color = '#e74c3c'; 
            } elseif ($status === Appointment::STATUS_SCHEDULED) {
                $color = '#f1c40f'; 
            }

            $events[] = [
                'id' => $creneau->getId(),
                'title' => $title,
                'start' => $start,
                'color' => $color,
                'extendedProps' => [
                    'status' => $status,
                    'cabinet' => $dispo && $dispo->getCabinet() ? $dispo->getCabinet()->getVille() : 'N/A',
                    'adresse' => $dispo && $dispo->getCabinet() ? $dispo->getCabinet()->getAdresse() : 'N/A'
                ]
            ];
        }

        return new JsonResponse($events);
    }
}
