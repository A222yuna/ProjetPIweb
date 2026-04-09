<?php

namespace App\Controller\Patient;

use App\Entity\Appointment;
use App\Entity\Creneau;
use App\Entity\User;
use App\Form\CreneauType;
use App\Repository\AppointmentRepository;
use App\Repository\CreneauRepository;
use App\Repository\DisponibiliteRepository;
use App\Repository\PsychologuePlanRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/patient/creneaux')]
#[IsGranted('ROLE_PATIENT')]
final class CreneauController extends AbstractController
{
    #[Route('/', name: 'app_patient_creneaux_index', methods: ['GET'])]
    public function index(CreneauRepository $creneaux): Response
    {
        $user = $this->getUser();
        \assert($user instanceof User);

        $items = $creneaux->findForPatient($user);
        usort($items, static function (Creneau $a, Creneau $b): int {
            $ad = ($a->getDateCreneau()?->format('Y-m-d') ?? '') . ' ' . ($a->getHeure()?->format('H:i:s') ?? '');
            $bd = ($b->getDateCreneau()?->format('Y-m-d') ?? '') . ' ' . ($b->getHeure()?->format('H:i:s') ?? '');
            return strcmp($bd, $ad);
        });

        return $this->render('patient/creneaux/index.html.twig', ['creneaux' => $items]);
    }

    #[Route('/book', name: 'app_patient_creneaux_book', methods: ['GET', 'POST'])]
    public function book(
        Request $request,
        DisponibiliteRepository $disponibilites,
        CreneauRepository $creneauxRepo,
        PsychologuePlanRepository $plans,
        AppointmentRepository $appointments,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();
        \assert($user instanceof User);

        $creneau = new Creneau();
        $form = $this->createForm(CreneauType::class, $creneau);
        $form->handleRequest($request);

        // For GET view only: show disponibilites with creneaux existing
        $available = $disponibilites->findWithCreneauxByCabinet();
        $availableGrouped = [];
        foreach ($available as $d) {
            $key = sprintf(
                '#%d - %s (%s-%s)',
                $d->getId() ?? 0,
                $d->getCabinet()?->getVille() ?? 'Cabinet',
                $d->getHeureDebut()?->format('H:i') ?? '--:--',
                $d->getHeureFin()?->format('H:i') ?? '--:--'
            );
            $availableGrouped[$key] = [];
            for ($i = 0; $i < 7; $i++) {
                $date = (new \DateTimeImmutable('today'))->modify("+$i day");
                $availableGrouped[$key][] = $date->format('d/m/Y');
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $dispo = $creneau->getDisponibilite();
            $date = $creneau->getDateCreneau();
            $heure = $creneau->getHeure();

            if (!$dispo || !$date || !$heure) {
                $this->addFlash('error', 'Formulaire incomplet.');
                return $this->redirectToRoute('app_patient_creneaux_book');
            }

            // Slot integrity checks: date must match disponibilite weekday and time must fit the window.
            $selectedIsoDay = (int) $date->format('N'); // 1 (Mon) ... 7 (Sun)
            if ($selectedIsoDay !== $dispo->getJour()) {
                $this->addFlash('error', 'La date choisie ne correspond pas au jour de disponibilité sélectionné');
                return $this->redirectToRoute('app_patient_creneaux_book');
            }
            $start = $dispo->getHeureDebut();
            $end = $dispo->getHeureFin();
            if (!$start || !$end) {
                $this->addFlash('error', 'Disponibilité invalide : horaires incomplets.');
                return $this->redirectToRoute('app_patient_creneaux_book');
            }
            $slotMinute = ((int) $heure->format('H') * 60) + (int) $heure->format('i');
            $startMinute = ((int) $start->format('H') * 60) + (int) $start->format('i');
            $endMinute = ((int) $end->format('H') * 60) + (int) $end->format('i');
            $duration = $dispo->getDureeConsultation();
            if ($slotMinute < $startMinute || $slotMinute >= $endMinute) {
                $this->addFlash('error', 'L\'heure choisie est hors de la plage de disponibilité');
                return $this->redirectToRoute('app_patient_creneaux_book');
            }
            if ($slotMinute + $duration > $endMinute) {
                $this->addFlash('error', 'Le créneau choisi dépasse l\'heure de fin de disponibilité');
                return $this->redirectToRoute('app_patient_creneaux_book');
            }
            if ((($slotMinute - $startMinute) % $duration) !== 0) {
                $this->addFlash('error', 'L\'heure choisie ne respecte pas la durée de consultation');
                return $this->redirectToRoute('app_patient_creneaux_book');
            }

            // RULE 5 — No past booking
            $today = new \DateTimeImmutable('today');
            if ($date < $today) {
                $this->addFlash('error', 'Vous ne pouvez pas réserver un créneau dans le passé');
                return $this->redirectToRoute('app_patient_creneaux_book');
            }

            // RULE 4 — No double booking
            if ($creneauxRepo->isSlotAlreadyBooked($dispo, $date, $heure)) {
                $this->addFlash('error', 'Ce créneau est déjà réservé, veuillez en choisir un autre');
                return $this->redirectToRoute('app_patient_creneaux_book');
            }

            // Find psychologue + plan based on date/heure
            $cabinet = $dispo->getCabinet();

            $dayOfWeek = strtoupper($date->format('l')); // Monday...
            $dayOfWeek = match ($dayOfWeek) {
                'MONDAY' => 'MONDAY',
                'TUESDAY' => 'TUESDAY',
                'WEDNESDAY' => 'WEDNESDAY',
                'THURSDAY' => 'THURSDAY',
                'FRIDAY' => 'FRIDAY',
                'SATURDAY' => 'SATURDAY',
                'SUNDAY' => 'SUNDAY',
                default => 'MONDAY',
            };
            $period = ((int) $heure->format('H') < 18) ? 'DAY' : 'NIGHT';

            $plan = null;
            if ($cabinet) {
                foreach ($cabinet->getPsyCabinets() as $psyCabinet) {
                    $candidate = $psyCabinet->getPsychologue();
                    if (!$candidate instanceof User) {
                        continue;
                    }
                    $candidatePlan = $plans->findOneForPsychologueDayPeriod($candidate, $dayOfWeek, $period);
                    if ($candidatePlan) {
                        $plan = $candidatePlan;
                        break;
                    }
                }
            }
            if (!$plan) {
                $this->addFlash('error', 'Aucun planning trouvé pour ce cabinet sur ce jour et cette période');
                return $this->redirectToRoute('app_patient_creneaux_book');
            }

            // RULE 6 — Respect max_appointments
            if ($appointments->countScheduledForPlan($plan) >= $plan->getMaxAppointments()) {
                $this->addFlash('error', 'Ce psychologue a atteint son nombre maximum de rendez-vous');
                return $this->redirectToRoute('app_patient_creneaux_book');
            }

            $creneau->setPatient($user);
            $creneau->setStatut(Creneau::STATUT_RESERVE);

            $appointment = new Appointment();
            $appointment->setPatient($user);
            $appointment->setPlan($plan);
            $appointment->setStatus(Appointment::STATUS_SCHEDULED);

            $em->persist($creneau);
            $em->persist($appointment);
            $em->flush();

            $this->addFlash('success', 'Votre créneau a été réservé avec succès');
            return $this->redirectToRoute('app_patient_creneaux_index');
        }

        return $this->render('patient/creneaux/book.html.twig', [
            'form' => $form,
            'disponibilites' => $available,
            'available_grouped' => $availableGrouped,
        ]);
    }

    #[Route('/{id}/cancel', name: 'app_patient_creneaux_cancel', methods: ['POST'])]
    public function cancel(
        int $id,
        Request $request,
        CreneauRepository $creneaux,
        AppointmentRepository $appointments,
        PsychologuePlanRepository $plans,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();
        \assert($user instanceof User);

        $creneau = $creneaux->find($id);
        if (!$creneau) {
            throw $this->createNotFoundException();
        }

        // RULE 7 — Only owner can cancel
        if ($creneau->getPatient()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('cancel_creneau_'.$creneau->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_patient_creneaux_index');
        }

        if ($creneau->getStatut() === Creneau::STATUT_ANNULE) {
            $this->addFlash('warning', 'Ce créneau est déjà annulé.');
            return $this->redirectToRoute('app_patient_creneaux_index');
        }

        // Find linked plan using same logic as booking
        $dispo = $creneau->getDisponibilite();
        $date = $creneau->getDateCreneau();
        $heure = $creneau->getHeure();
        if (!$dispo || !$date || !$heure) {
            $this->addFlash('error', 'Créneau invalide.');
            return $this->redirectToRoute('app_patient_creneaux_index');
        }
        $cabinet = $dispo->getCabinet();
        $dayOfWeek = strtoupper($date->format('l'));
        $dayOfWeek = match ($dayOfWeek) {
            'MONDAY' => 'MONDAY',
            'TUESDAY' => 'TUESDAY',
            'WEDNESDAY' => 'WEDNESDAY',
            'THURSDAY' => 'THURSDAY',
            'FRIDAY' => 'FRIDAY',
            'SATURDAY' => 'SATURDAY',
            'SUNDAY' => 'SUNDAY',
            default => 'MONDAY',
        };
        $period = ((int) $heure->format('H') < 18) ? 'DAY' : 'NIGHT';
        $plan = null;
        if ($cabinet) {
            foreach ($cabinet->getPsyCabinets() as $psyCabinet) {
                $candidate = $psyCabinet->getPsychologue();
                if (!$candidate instanceof User) {
                    continue;
                }
                $candidatePlan = $plans->findOneForPsychologueDayPeriod($candidate, $dayOfWeek, $period);
                if ($candidatePlan) {
                    $plan = $candidatePlan;
                    break;
                }
            }
        }

        $appointment = $plan
            ? $appointments->findLatestForPatientAndPlanByStatuses($user, $plan, [Appointment::STATUS_SCHEDULED, Appointment::STATUS_COMPLETED])
            : null;

        // RULE 8 — Cannot cancel completed appointment
        if ($appointment && $appointment->getStatus() === Appointment::STATUS_COMPLETED) {
            $this->addFlash('error', "Impossible d'annuler un rendez-vous déjà terminé");
            return $this->redirectToRoute('app_patient_creneaux_index');
        }

        $creneau->setStatut(Creneau::STATUT_ANNULE);

        // RULE 9 — Auto-cancel appointment
        if ($appointment) {
            $appointment->setStatus(Appointment::STATUS_CANCELLED);
        }

        $em->flush();
        $this->addFlash('success', 'Votre rendez-vous a été annulé avec succès');

        return $this->redirectToRoute('app_patient_creneaux_index');
    }
}

