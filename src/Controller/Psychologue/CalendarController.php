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
    #[Route('/', name: 'app_psychologue_calendar_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('psychologue/calendar/index.html.twig');
    }

    #[Route('/events', name: 'app_psychologue_calendar_events', methods: ['GET'])]
    public function events(AppointmentRepository $appRepo, CreneauRepository $creneauRepo): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'User not found'], 403);
        }

        $events = [];

        // 1. Get Appointments via Plans (Old system)
        $appointments = $appRepo->findForPsychologue($user->getId());
        foreach ($appointments as $app) {
            $period = $app->getPlan()->getPeriod(); // e.g., "14:00 - 15:00"
            $times = explode(' - ', $period);
            if (count($times) === 2) {
                $events[] = [
                    'id' => 'app_' . $app->getId(),
                    'title' => 'RDV: ' . $app->getPatient()->getNom() . ' ' . $app->getPatient()->getPrenom(),
                    'start' => $app->getPlan()->getDayOfWeek() . 'T' . $times[0],
                    'end' => $app->getPlan()->getDayOfWeek() . 'T' . $times[1],
                    'backgroundColor' => '#2a6f5b',
                    'borderColor' => '#2a6f5b',
                    'textColor' => '#ffffff',
                    'extendedProps' => [
                        'type' => 'appointment',
                        'status' => $app->getStatus(),
                        'patientEmail' => $app->getPatient()->getEmail(),
                        'plan' => $app->getPlan()->getDayOfWeek() . ' ' . $app->getPlan()->getPeriod()
                    ]
                ];
            }
        }

        // 2. Get Creneaux (New system)
        $allCreneaux = $creneauRepo->createQueryBuilder('c')
            ->join('c.disponibilite', 'd')
            ->join('d.cabinet', 'cab')
            ->join('cab.psyCabinets', 'pc')
            ->where('pc.psychologue = :psy')
            ->setParameter('psy', $user)
            ->getQuery()
            ->getResult();

        foreach ($allCreneaux as $creneau) {
            if (!$creneau->getPatient()) continue;

            $start = $creneau->getDateCreneau()->format('Y-m-d') . 'T' . $creneau->getHeure()->format('H:i:s');
            // Assume 1 hour duration if not specified
            $end = $creneau->getDateCreneau()->format('Y-m-d') . 'T' . $creneau->getHeure()->modify('+1 hour')->format('H:i:s');
            
            $events[] = [
                'id' => 'creneau_' . $creneau->getId(),
                'title' => 'RDV: ' . $creneau->getPatient()->getNom(),
                'start' => $start,
                'end' => $end,
                'backgroundColor' => '#3498db',
                'borderColor' => '#2980b9',
                'textColor' => '#ffffff',
                'extendedProps' => [
                    'type' => 'creneau',
                    'cabinet' => $creneau->getDisponibilite()->getCabinet()->getNom(),
                    'patientEmail' => $creneau->getPatient()->getEmail(),
                    'status' => 'CONFIRMED'
                ]
            ];
        }

        return $this->json($events);
    }
}
