<?php

namespace App\Controller\Psychologue;

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

#[Route('/psychologue/calendar')]
#[IsGranted('ROLE_PSYCHOLOGUE')]
final class CalendarController extends AbstractController
{
    #[Route('/', name: 'app_psychologue_calendar', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('psychologue/calendar/index.html.twig');
    }

    #[Route('/events', name: 'app_psychologue_calendar_events', methods: ['GET'])]
    public function events(AppointmentRepository $appointments, CreneauRepository $creneaux): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'User not found'], 403);
        }

        // Get all creneaux where the cabinet is linked to this psychologue
        $allCreneaux = $creneaux->createQueryBuilder('c')
            ->join('c.disponibilite', 'd')
            ->join('d.cabinet', 'cab')
            ->join('cab.psyCabinets', 'pc')
            ->where('pc.psychologue = :psy')
            ->setParameter('psy', $user)
            ->getQuery()
            ->getResult();

        $events = [];
        foreach ($allCreneaux as $creneau) {
            /** @var Creneau $creneau */
            $patient = $creneau->getPatient();
            if (!$patient) continue;

            $heure = $creneau->getHeure();
            $date = $creneau->getDateCreneau();
            
            if (!$date || !$heure) continue;

            // Find matching appointment to get real status
            $dayMap = [
                'MONDAY' => 'MONDAY', 'TUESDAY' => 'TUESDAY', 'WEDNESDAY' => 'WEDNESDAY',
                'THURSDAY' => 'THURSDAY', 'FRIDAY' => 'FRIDAY', 'SATURDAY' => 'SATURDAY', 'SUNDAY' => 'SUNDAY'
            ];
            $dayOfWeek = $dayMap[strtoupper($date->format('l'))] ?? 'MONDAY';
            $period = ((int)$heure->format('H') < 18) ? 'DAY' : 'NIGHT';

            $appointment = $appointments->createQueryBuilder('a')
                ->join('a.plan', 'p')
                ->where('a.patient = :patient')
                ->andWhere('p.psychologue = :psy')
                ->andWhere('p.dayOfWeek = :day')
                ->andWhere('p.period = :period')
                ->setParameter('patient', $patient)
                ->setParameter('psy', $user)
                ->setParameter('day', $dayOfWeek)
                ->setParameter('period', $period)
                ->orderBy('a.id', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            $start = $date->format('Y-m-d') . 'T' . $heure->format('H:i:s');
            
            $status = $creneau->getStatut();
            $color = '#5B9BD5'; // Blue default

            if ($appointment) {
                $status = $appointment->getStatus();
                $color = match($status) {
                    Appointment::STATUS_PAID => '#2ecc71', // Bright Green
                    Appointment::STATUS_CONFIRMED => '#3498db', // Blue
                    Appointment::STATUS_SCHEDULED => '#f1c40f', // Yellow
                    Appointment::STATUS_COMPLETED => '#95a5a6', // Gray
                    Appointment::STATUS_CANCELLED => '#e74c3c', // Red
                    default => '#5B9BD5'
                };
            } elseif ($creneau->getStatut() === Creneau::STATUT_ANNULE) {
                $color = '#e74c3c';
            }

            $events[] = [
                'id' => $creneau->getId(),
                'title' => $patient->getPrenom() . ' ' . $patient->getNom(),
                'start' => $start,
                'color' => $color,
                'extendedProps' => [
                    'status' => $status,
                    'patientEmail' => $patient->getEmail(),
                    'plan' => $dayOfWeek . ' (' . $period . ')'
                ]
            ];
        }

        return new JsonResponse($events);
    }
}
