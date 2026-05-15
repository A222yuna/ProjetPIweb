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
            ->leftJoin('c.disponibilite', 'd')->addSelect('d')
            ->leftJoin('d.cabinet', 'cab')->addSelect('cab')
            ->leftJoin('cab.psyCabinets', 'pc')->addSelect('pc')
            ->leftJoin('pc.psychologue', 'psy')->addSelect('psy')
            ->where('c.patient = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        // Get all appointments for this patient indexed by plan
        $myAppointments = $appointments->findForPatient($user->getId() ?? 0);
        $apptByPlan = [];
        foreach ($myAppointments as $appt) {
            $planId = $appt->getPlan()?->getId();
            if ($planId) {
                $apptByPlan[$planId] = $appt->getStatus();
            }
        }

        $events = [];
        foreach ($myCreneaux as $creneau) {
            $dispo = $creneau->getDisponibilite();
            $cabinet = $dispo?->getCabinet();
            $psy = null;

            if ($cabinet) {
                $psyCabinet = $cabinet->getPsyCabinets()->first();
                if ($psyCabinet) {
                    $psy = $psyCabinet->getPsychologue();
                }
            }

            // Determine status from appointment if available
            $status = $creneau->getStatut();
            if ($psy) {
                // Find appointment for this psy
                foreach ($myAppointments as $appt) {
                    if ($appt->getPlan()?->getPsychologue()?->getId() === $psy->getId()) {
                        $status = $appt->getStatus();
                        break;
                    }
                }
            }

            $color = match($status) {
                'SCHEDULED', 'RESERVE' => '#ffc107', // Warning Yellow
                'CONFIRMED' => '#0dcaf0',           // Info Cyan
                'PAID'      => '#0d6efd',           // Primary Blue
                'COMPLETED' => '#198754',           // Success Green
                'CANCELLED' => '#dc3545',           // Danger Red
                default     => '#6c757d',           // Secondary Gray
            };

            $date = $creneau->getDateCreneau();
            $heure = $creneau->getHeure();
            if (!$date || !$heure) continue;

            $events[] = [
                'id'    => $creneau->getId(),
                'title' => $psy ? 'Dr. ' . $psy->getPrenom() . ' ' . $psy->getNom() : 'Rendez-vous',
                'start' => $date->format('Y-m-d') . 'T' . $heure->format('H:i:s'),
                'color' => $color,
                'extendedProps' => [
                    'status'  => $status,
                    'cabinet' => $cabinet ? $cabinet->getVille() . ' - ' . $cabinet->getAdresse() : 'N/A',
                    'adresse' => $cabinet ? $cabinet->getAdresse() : 'N/A',
                ],
            ];
        }

        return new JsonResponse($events);
    }
}
